<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverWallet extends Model
{
    public const TYPE_INDEPENDENT = 'independent';
    public const TYPE_FLEET = 'fleet';

    protected $guarded = [];

    protected $casts = [
        'balance' => 'decimal:2',
        'min_balance' => 'decimal:2',
        'wallet_blocked' => 'boolean',
        'last_topup_at' => 'datetime',
    ];

    protected $appends = ['effective_min_balance', 'needs_topup', 'can_receive_jobs'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(DriverWalletTransaction::class, 'user_id', 'user_id');
    }

    public function isIndependent(): bool
    {
        return $this->driver_type === self::TYPE_INDEPENDENT;
    }

    public function getEffectiveMinBalanceAttribute(): float
    {
        if ($this->min_balance !== null) {
            return (float) $this->min_balance;
        }

        return (float) config('kanguro.driver_wallet_min_balance', 200);
    }

    public function getNeedsTopupAttribute(): bool
    {
        if (! $this->isIndependent()) {
            return false;
        }

        return (float) $this->balance < $this->effective_min_balance;
    }

    public function getCanReceiveJobsAttribute(): bool
    {
        if ($this->wallet_blocked) {
            return false;
        }

        if (! $this->isIndependent()) {
            return true;
        }

        return ! $this->needs_topup;
    }
}
