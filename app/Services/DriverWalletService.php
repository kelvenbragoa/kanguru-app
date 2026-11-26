<?php

namespace App\Services;

use App\Models\DriverWallet;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DriverWalletService
{
    public function ensureWallet(User|int $user, string $defaultType = DriverWallet::TYPE_INDEPENDENT): DriverWallet
    {
        $userId = $user instanceof User ? $user->id : $user;

        return DriverWallet::query()->firstOrCreate(
            ['user_id' => $userId],
            [
                'driver_type' => $defaultType,
                'balance' => 0,
                'min_balance' => null,
            ]
        );
    }

    public function canReceiveJobs(User|int $user): bool
    {
        if (! config('kanguro.driver_wallet_enabled', true)) {
            return true;
        }

        $wallet = $this->ensureWallet($user);

        return $wallet->can_receive_jobs;
    }

    public function assertCanReceiveJobs(User|int $user): void
    {
        if ($this->canReceiveJobs($user)) {
            return;
        }

        $wallet = $this->ensureWallet($user);
        $min = $wallet->effective_min_balance;
        $bal = (float) $wallet->balance;

        throw new \InvalidArgumentException(
            "Saldo insuficiente para receber viagens. Saldo actual: {$bal} MT. Mínimo: {$min} MT. Recarrega a carteira Kanguro."
        );
    }

    public function requestTopup(User $user, float $amount, string $method, ?string $externalRef = null, ?string $notes = null): DriverWalletTransaction
    {
        if ($amount < (float) config('kanguro.driver_wallet_min_topup', 50)) {
            throw new \InvalidArgumentException('Valor de recarga demasiado baixo.');
        }

        $this->ensureWallet($user);

        return DriverWalletTransaction::create([
            'user_id' => $user->id,
            'type' => DriverWalletTransaction::TYPE_TOPUP,
            'amount' => round($amount, 2),
            'balance_after' => (float) $this->ensureWallet($user)->balance,
            'status' => DriverWalletTransaction::STATUS_PENDING,
            'method' => $method,
            'external_ref' => $externalRef,
            'notes' => $notes,
            'created_by' => $user->id,
        ]);
    }

    public function confirmTopup(DriverWalletTransaction $tx, ?User $admin = null): DriverWallet
    {
        if ($tx->type !== DriverWalletTransaction::TYPE_TOPUP) {
            throw new \InvalidArgumentException('Transacção inválida.');
        }
        if ($tx->status === DriverWalletTransaction::STATUS_COMPLETED) {
            return $this->ensureWallet($tx->user_id);
        }
        if ($tx->status === DriverWalletTransaction::STATUS_REJECTED) {
            throw new \InvalidArgumentException('Recarga já rejeitada.');
        }

        return DB::transaction(function () use ($tx, $admin) {
            $wallet = DriverWallet::query()->where('user_id', $tx->user_id)->lockForUpdate()->firstOrFail();
            $newBalance = round((float) $wallet->balance + (float) $tx->amount, 2);
            $wallet->update([
                'balance' => $newBalance,
                'last_topup_at' => now(),
                'wallet_blocked' => false,
            ]);
            $tx->update([
                'status' => DriverWalletTransaction::STATUS_COMPLETED,
                'balance_after' => $newBalance,
                'completed_at' => now(),
                'created_by' => $admin?->id ?? $tx->created_by,
            ]);

            return $wallet->fresh();
        });
    }

    public function rejectTopup(DriverWalletTransaction $tx, ?string $notes = null, ?User $admin = null): DriverWalletTransaction
    {
        if ($tx->status !== DriverWalletTransaction::STATUS_PENDING) {
            throw new \InvalidArgumentException('Só recargas pendentes podem ser rejeitadas.');
        }

        $tx->update([
            'status' => DriverWalletTransaction::STATUS_REJECTED,
            'notes' => trim(($tx->notes ? $tx->notes.' | ' : '').($notes ?? 'Rejeitada')),
            'created_by' => $admin?->id ?? $tx->created_by,
            'completed_at' => now(),
        ]);

        return $tx->fresh();
    }

    public function adminCredit(User $driver, float $amount, string $method = 'cash_office', ?string $notes = null, ?User $admin = null): DriverWallet
    {
        $tx = $this->requestTopup($driver, $amount, $method, null, $notes ?? 'Recarga confirmada no escritório');
        if ($admin) {
            $tx->update(['created_by' => $admin->id]);
        }

        return $this->confirmTopup($tx->fresh(), $admin);
    }

    /**
     * Debita comissão da carteira do motorista independente após viagem/entrega.
     */
    public function debitCommission(Order $order, float $commission, string $label): ?DriverWalletTransaction
    {
        if ($commission <= 0 || ! $order->agent_user_id) {
            return null;
        }

        if (! config('kanguro.driver_wallet_enabled', true)) {
            return null;
        }

        $wallet = $this->ensureWallet($order->agent_user_id);
        if (! $wallet->isIndependent()) {
            return null;
        }

        $existing = DriverWalletTransaction::query()
            ->where('type', DriverWalletTransaction::TYPE_COMMISSION)
            ->where('reference_type', 'order')
            ->where('reference_id', $order->id)
            ->where('status', DriverWalletTransaction::STATUS_COMPLETED)
            ->exists();
        if ($existing) {
            return null;
        }

        return DB::transaction(function () use ($order, $commission, $label, $wallet) {
            $wallet = DriverWallet::query()->where('id', $wallet->id)->lockForUpdate()->firstOrFail();
            $newBalance = round((float) $wallet->balance - $commission, 2);
            $wallet->update(['balance' => $newBalance]);

            return DriverWalletTransaction::create([
                'user_id' => $wallet->user_id,
                'type' => DriverWalletTransaction::TYPE_COMMISSION,
                'amount' => round(-$commission, 2),
                'balance_after' => $newBalance,
                'status' => DriverWalletTransaction::STATUS_COMPLETED,
                'method' => 'system',
                'reference_type' => 'order',
                'reference_id' => $order->id,
                'notes' => $label,
                'completed_at' => now(),
            ]);
        });
    }

    public function summary(User|int $user): array
    {
        $wallet = $this->ensureWallet($user);
        $pending = DriverWalletTransaction::query()
            ->where('user_id', $wallet->user_id)
            ->where('status', DriverWalletTransaction::STATUS_PENDING)
            ->where('type', DriverWalletTransaction::TYPE_TOPUP)
            ->orderByDesc('created_at')
            ->get();

        $recent = DriverWalletTransaction::query()
            ->where('user_id', $wallet->user_id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'driver_type' => $wallet->driver_type,
            'driver_type_label' => $wallet->isIndependent() ? 'Independente' : 'Frota / escritório',
            'balance' => (float) $wallet->balance,
            'min_balance' => $wallet->effective_min_balance,
            'needs_topup' => $wallet->needs_topup,
            'can_receive_jobs' => $wallet->can_receive_jobs,
            'wallet_blocked' => (bool) $wallet->wallet_blocked,
            'last_topup_at' => $wallet->last_topup_at?->toIso8601String(),
            'currency' => 'MZN',
            'pending_topups' => $pending,
            'recent' => $recent,
        ];
    }
}
