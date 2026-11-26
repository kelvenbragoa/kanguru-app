<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class FinanceController extends Controller
{
    public function summary(Request $request)
    {
        $payable = LedgerEntry::query()
            ->where('status', LedgerEntry::STATUS_PAYABLE)
            ->selectRaw('beneficiary_type, sum(amount) as total')
            ->groupBy('beneficiary_type')
            ->pluck('total', 'beneficiary_type');

        return response()->json([
            'status' => 'success',
            'data' => [
                'partner_payable' => round((float) ($payable[LedgerEntry::TYPE_PARTNER] ?? 0), 2),
                'courier_payable' => round((float) ($payable[LedgerEntry::TYPE_COURIER] ?? 0), 2),
                'platform_revenue' => round((float) ($payable[LedgerEntry::TYPE_PLATFORM] ?? 0), 2),
                'open_payouts' => Payout::query()->where('status', Payout::STATUS_OPEN)->count(),
                'cities' => config('kanguro.cities'),
                'rates' => [
                    'partner_commission' => config('kanguro.partner_commission_rate'),
                    'taxi_commission' => config('kanguro.taxi_platform_commission'),
                    'service_fee_rate' => config('kanguro.service_fee_rate'),
                ],
            ],
        ]);
    }

    public function ledger(Request $request)
    {
        $query = LedgerEntry::query()
            ->with(['shop:id,name,city', 'user:id,name'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('beneficiary_type')) {
            $query->where('beneficiary_type', $request->beneficiary_type);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(min(50, max(1, (int) $request->query('per_page', 30)))),
        ]);
    }

    public function payouts(Request $request)
    {
        $query = Payout::query()->with(['shop:id,name', 'user:id,name'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(min(50, max(1, (int) $request->query('per_page', 30)))),
        ]);
    }

    public function createPayouts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'beneficiary_type' => 'nullable|in:partner,courier',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $types = $request->filled('beneficiary_type')
            ? [$request->beneficiary_type]
            : [LedgerEntry::TYPE_PARTNER, LedgerEntry::TYPE_COURIER];

        $created = [];

        DB::transaction(function () use ($request, $types, &$created) {
            foreach ($types as $type) {
                $groups = LedgerEntry::query()
                    ->where('beneficiary_type', $type)
                    ->where('status', LedgerEntry::STATUS_PAYABLE)
                    ->whereNull('payout_id')
                    ->whereDate('created_at', '>=', $request->period_start)
                    ->whereDate('created_at', '<=', $request->period_end)
                    ->get()
                    ->groupBy(fn (LedgerEntry $e) => ($e->shop_id ?: 0).':'.($e->user_id ?: 0));

                foreach ($groups as $entries) {
                    $first = $entries->first();
                    $amount = round((float) $entries->sum('amount'), 2);
                    if ($amount <= 0) {
                        continue;
                    }

                    $payout = Payout::create([
                        'beneficiary_type' => $type,
                        'beneficiary_id' => $first->beneficiary_id,
                        'shop_id' => $first->shop_id,
                        'user_id' => $first->user_id,
                        'period_start' => $request->period_start,
                        'period_end' => $request->period_end,
                        'amount' => $amount,
                        'status' => Payout::STATUS_OPEN,
                        'created_by' => $request->user()->id,
                    ]);

                    LedgerEntry::query()
                        ->whereIn('id', $entries->pluck('id'))
                        ->update(['payout_id' => $payout->id]);

                    $created[] = $payout->load(['shop:id,name', 'user:id,name']);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => count($created).' ciclo(s) criado(s)',
            'data' => $created,
        ], 201);
    }

    public function markPaid(Request $request, string $id)
    {
        $payout = Payout::query()->findOrFail($id);
        if ($payout->status === Payout::STATUS_PAID) {
            return response()->json(['status' => 'error', 'message' => 'Já está pago'], 422);
        }

        DB::transaction(function () use ($payout, $request) {
            $payout->update([
                'status' => Payout::STATUS_PAID,
                'paid_at' => now(),
                'notes' => $request->input('notes', $payout->notes),
            ]);
            $payout->entries()->update(['status' => LedgerEntry::STATUS_PAID]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Pagamento registado',
            'data' => $payout->fresh(['shop', 'user', 'entries']),
        ]);
    }

    public function businessEarnings(Request $request)
    {
        $shopIds = Shop::query()->where('owner_user_id', $request->user()->id)->pluck('id');

        $entries = LedgerEntry::query()
            ->where('beneficiary_type', LedgerEntry::TYPE_PARTNER)
            ->whereIn('shop_id', $shopIds)
            ->latest()
            ->limit(100)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'payable' => round((float) $entries->where('status', LedgerEntry::STATUS_PAYABLE)->sum('amount'), 2),
                'paid' => round((float) $entries->where('status', LedgerEntry::STATUS_PAID)->sum('amount'), 2),
                'entries' => $entries,
            ],
        ]);
    }
}
