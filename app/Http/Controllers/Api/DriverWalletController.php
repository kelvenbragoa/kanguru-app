<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DriverWallet;
use App\Models\DriverWalletTransaction;
use App\Models\User;
use App\Services\DriverWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DriverWalletController extends Controller
{
    public function __construct(private DriverWalletService $wallets)
    {
    }

    public function show(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->wallets->summary($request->user()),
        ]);
    }

    public function requestTopup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:'.(float) config('kanguro.driver_wallet_min_topup', 50),
            'method' => 'required|in:mpesa,emola,cash_office,card',
            'external_ref' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $tx = $this->wallets->requestTopup(
                $request->user(),
                (float) $request->amount,
                $request->method,
                $request->external_ref,
                $request->notes
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido de recarga enviado. A equipa confirma após pagamento.',
            'data' => $tx,
        ], 201);
    }

    public function adminIndex(Request $request)
    {
        $query = DriverWallet::query()
            ->with(['user:id,name,email,phone'])
            ->orderByDesc('updated_at');

        if ($request->filled('driver_type')) {
            $query->where('driver_type', $request->driver_type);
        }
        if ($request->boolean('needs_topup')) {
            $min = (float) config('kanguro.driver_wallet_min_balance', 200);
            $query->where('driver_type', DriverWallet::TYPE_INDEPENDENT)
                ->whereRaw('balance < COALESCE(min_balance, ?)', [$min]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(min(50, max(1, (int) $request->query('per_page', 30)))),
        ]);
    }

    public function adminPendingTopups(Request $request)
    {
        $rows = DriverWalletTransaction::query()
            ->with(['user:id,name,email,phone', 'creator:id,name'])
            ->where('type', DriverWalletTransaction::TYPE_TOPUP)
            ->where('status', DriverWalletTransaction::STATUS_PENDING)
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 30))));

        return response()->json(['status' => 'success', 'data' => $rows]);
    }

    public function adminShow(Request $request, string $userId)
    {
        $user = User::query()->findOrFail($userId);

        return response()->json([
            'status' => 'success',
            'data' => $this->wallets->summary($user),
        ]);
    }

    public function adminSetType(Request $request, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'driver_type' => 'required|in:independent,fleet',
            'min_balance' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $wallet = $this->wallets->ensureWallet((int) $userId, $request->driver_type);
        $wallet->update([
            'driver_type' => $request->driver_type,
            'min_balance' => $request->filled('min_balance') ? $request->min_balance : $wallet->min_balance,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de motorista actualizado',
            'data' => $this->wallets->summary((int) $userId),
        ]);
    }

    public function adminCredit(Request $request, string $userId)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:'.(float) config('kanguro.driver_wallet_min_topup', 50),
            'method' => 'nullable|in:mpesa,emola,cash_office,card',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::query()->findOrFail($userId);

        try {
            $wallet = $this->wallets->adminCredit(
                $user,
                (float) $request->amount,
                $request->input('method', 'cash_office'),
                $request->notes,
                $request->user()
            );
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Saldo creditado',
            'data' => [
                'balance' => (float) $wallet->balance,
                'summary' => $this->wallets->summary($user),
            ],
        ]);
    }

    public function adminConfirmTopup(Request $request, string $id)
    {
        $tx = DriverWalletTransaction::query()->findOrFail($id);

        try {
            $wallet = $this->wallets->confirmTopup($tx, $request->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Recarga confirmada',
            'data' => [
                'balance' => (float) $wallet->balance,
                'transaction' => $tx->fresh(),
            ],
        ]);
    }

    public function adminRejectTopup(Request $request, string $id)
    {
        $tx = DriverWalletTransaction::query()->findOrFail($id);

        try {
            $tx = $this->wallets->rejectTopup($tx, $request->input('notes'), $request->user());
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Recarga rejeitada',
            'data' => $tx,
        ]);
    }
}
