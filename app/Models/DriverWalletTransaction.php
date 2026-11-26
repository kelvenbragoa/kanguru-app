<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverWalletTransaction extends Model
{
    public const TYPE_TOPUP = 'topup';
    public const TYPE_COMMISSION = 'commission';
    public const TYPE_ADJUSTMENT = 'adjustment';
    public const TYPE_REFUND = 'refund';

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['type_label', 'status_label'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_TOPUP => 'Recarga',
            self::TYPE_COMMISSION => 'Comissão',
            self::TYPE_ADJUSTMENT => 'Ajuste',
            self::TYPE_REFUND => 'Reembolso',
            default => $this->type,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_COMPLETED => 'Concluída',
            self::STATUS_REJECTED => 'Rejeitada',
            default => $this->status,
        };
    }
}
