<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    public const CURRENCY = 'MZN';

    public const METHODS = ['mpesa', 'emola', 'cash', 'card'];

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected $appends = ['currency', 'method_label'];

    public function getCurrencyAttribute(): string
    {
        return self::CURRENCY;
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'mpesa' => 'M-Pesa',
            'emola' => 'e-Mola',
            'cash' => 'Dinheiro na entrega',
            'card' => 'Cartão',
            default => $this->payment_method,
        };
    }

    // Relacionamentos
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
