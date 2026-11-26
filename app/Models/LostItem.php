<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LostItem extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_DRIVER_CONTACTED = 'driver_contacted';
    public const STATUS_RETURNING = 'returning';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'return_fee' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    protected $appends = ['status_label'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'Aberto',
            self::STATUS_DRIVER_CONTACTED => 'Motorista contactado',
            self::STATUS_RETURNING => 'A devolver',
            self::STATUS_RETURNED => 'Devolvido',
            self::STATUS_CLOSED => 'Fechado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }
}
