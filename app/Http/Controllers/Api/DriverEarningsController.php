<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LedgerEntry;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DriverEarningsController extends Controller
{
    public function show(Request $request)
    {
        $period = $request->string('period')->toString() ?: 'week';
        if (! in_array($period, ['today', 'week', 'month', 'all'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid period',
            ], 422);
        }

        [$from, $to, $label] = $this->range($period);
        $driverId = $request->user()->id;

        $ledgerQuery = LedgerEntry::query()
            ->where('beneficiary_type', LedgerEntry::TYPE_COURIER)
            ->where(function ($q) use ($driverId) {
                $q->where('beneficiary_id', $driverId)->orWhere('user_id', $driverId);
            })
            ->whereIn('status', [LedgerEntry::STATUS_PENDING, LedgerEntry::STATUS_PAYABLE, LedgerEntry::STATUS_PAID]);

        $periodLedger = (clone $ledgerQuery)
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to))
            ->orderByDesc('created_at')
            ->get();

        $lifetime = (clone $ledgerQuery)->get();
        [$monthFrom, $monthTo] = $this->range('month');
        $monthLedger = (clone $ledgerQuery)
            ->where('created_at', '>=', $monthFrom)
            ->where('created_at', '<=', $monthTo)
            ->get();
        [$weekFrom, $weekTo] = $this->range('week');
        $weekLedger = (clone $ledgerQuery)
            ->where('created_at', '>=', $weekFrom)
            ->where('created_at', '<=', $weekTo)
            ->get();

        $periodOrders = $this->deliveredOrders($driverId)
            ->when($from, fn ($query) => $query->where('delivered_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('delivered_at', '<=', $to))
            ->get();

        $payable = round((float) (clone $ledgerQuery)->where('status', LedgerEntry::STATUS_PAYABLE)->sum('amount'), 2);
        $pending = round((float) (clone $ledgerQuery)->where('status', LedgerEntry::STATUS_PENDING)->sum('amount'), 2);
        $paid = round((float) (clone $ledgerQuery)->where('status', LedgerEntry::STATUS_PAID)->sum('amount'), 2);

        // Fallback se ainda não houver ledger (pedidos antigos)
        $periodTotal = $periodLedger->isNotEmpty()
            ? round((float) $periodLedger->sum('amount'), 2)
            : round((float) $periodOrders->sum(fn (Order $o) => $o->courier_due ?: $o->delivery_fee), 2);

        $days = $this->groupLedgerByDay($periodLedger, $from, $to, $period);

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'period_label' => $label,
                'from' => $from?->toIso8601String(),
                'to' => $to?->toIso8601String(),
                'currency' => 'MZN',
                'total' => $periodTotal,
                'deliveries' => $periodOrders->count(),
                'worked_minutes' => $this->workedMinutes($periodOrders),
                'average_per_delivery' => $periodOrders->isEmpty()
                    ? 0
                    : round($periodTotal / max(1, $periodOrders->count()), 2),
                'payable' => $payable,
                'pending' => $pending,
                'paid' => $paid,
                'days' => $days,
                'recent' => $periodLedger
                    ->take(10)
                    ->values()
                    ->map(fn (LedgerEntry $entry) => [
                        'ledger_id' => $entry->id,
                        'reference_type' => $entry->reference_type,
                        'reference_id' => $entry->reference_id,
                        'amount' => (float) $entry->amount,
                        'status' => $entry->status,
                        'description' => $entry->description,
                        'created_at' => $entry->created_at?->toIso8601String(),
                    ]),
                'lifetime' => [
                    'total' => round((float) $lifetime->sum('amount'), 2),
                    'deliveries' => $lifetime->count(),
                ],
                'month' => [
                    'total' => round((float) $monthLedger->sum('amount'), 2),
                    'deliveries' => $monthLedger->count(),
                ],
                'week' => [
                    'total' => round((float) $weekLedger->sum('amount'), 2),
                    'deliveries' => $weekLedger->count(),
                ],
            ],
        ]);
    }

    private function deliveredOrders(int $driverId)
    {
        return Order::query()
            ->where('agent_user_id', $driverId)
            ->whereHas('orderStatus', fn ($q) => $q->where('name', 'delivered'))
            ->whereNotNull('delivered_at');
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function range(string $period): array
    {
        $now = Carbon::now('Africa/Maputo');

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Hoje'],
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'Esta semana'],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'Este mês'],
            default => [null, null, 'Tudo'],
        };
    }

    private function workedMinutes($orders): int
    {
        return (int) $orders->sum(function (Order $order) {
            if (! $order->collected_at || ! $order->delivered_at) {
                return 0;
            }

            return max(0, $order->collected_at->diffInMinutes($order->delivered_at));
        });
    }

    private function groupLedgerByDay($entries, ?Carbon $from, ?Carbon $to, string $period): array
    {
        if ($period === 'all' || ! $from || ! $to) {
            return [];
        }

        $grouped = $entries->groupBy(fn (LedgerEntry $e) => $e->created_at?->timezone('Africa/Maputo')->toDateString());
        $days = [];
        $cursor = $from->copy()->startOfDay();
        while ($cursor->lte($to)) {
            $key = $cursor->toDateString();
            $bucket = $grouped->get($key, collect());
            $days[] = [
                'date' => $key,
                'total' => round((float) $bucket->sum('amount'), 2),
                'count' => $bucket->count(),
            ];
            $cursor->addDay();
        }

        return $days;
    }
}
