<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';

    protected $guarded = [];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'beneficiary_label'];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function entries()
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === self::STATUS_PAID ? 'Pago' : 'Aberto';
    }

    public function getBeneficiaryLabelAttribute(): string
    {
        return match ($this->beneficiary_type) {
            LedgerEntry::TYPE_PARTNER => $this->shop?->name ?? 'Parceiro',
            LedgerEntry::TYPE_COURIER => $this->user?->name ?? 'Estafeta/Motorista',
            default => 'Kanguro',
        };
    }
}
