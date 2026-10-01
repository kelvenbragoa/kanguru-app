<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Coupon extends Model
{
    protected $guarded = [];

    protected $casts = [
        'value' => 'float',
        'min_subtotal' => 'float',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function discountFor(float $subtotal, float $deliveryFee): float
    {
        if (! $this->is_active || ($this->expires_at && $this->expires_at->isPast())) {
            throw new InvalidArgumentException('Este cupão já não está ativo.');
        }

        if ($subtotal < $this->min_subtotal) {
            throw new InvalidArgumentException('O pedido não atinge o mínimo deste cupão.');
        }

        $base = $this->applies_to === 'delivery' ? $deliveryFee : $subtotal;
        $discount = $this->discount_type === 'percent'
            ? round($base * ($this->value / 100), 2)
            : (float) $this->value;

        return round(min($discount, $base), 2);
    }
}
