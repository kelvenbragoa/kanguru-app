<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceBooking extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REJECTED = 'rejected';

    protected $guarded = [];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'price' => 'decimal:2',
        'latitude' => 'float',
        'longitude' => 'float',
        'accepted_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'customer_status_label'];

    protected $hidden = ['completion_code'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function offering()
    {
        return $this->belongsTo(ServiceOffering::class, 'service_offering_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'À espera do prestador',
            self::STATUS_ACCEPTED => 'Aceite',
            self::STATUS_IN_PROGRESS => 'Em execução',
            self::STATUS_COMPLETED => 'Concluído',
            self::STATUS_CANCELLED => 'Cancelado',
            self::STATUS_REJECTED => 'Recusado',
            default => $this->status,
        };
    }

    public function getCustomerStatusLabelAttribute(): string
    {
        return $this->status_label;
    }

    public static function generateCode(): string
    {
        return 'SRV-'.now()->format('ymd').'-'.random_int(1000, 9999);
    }

    public static function generateCompletionCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public function canCancelByCustomer(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_ACCEPTED], true);
    }

    public function toCustomerArray(): array
    {
        $payload = $this->toArray();
        $payload['completion_code'] = $this->attributes['completion_code'] ?? null;
        $payload['requires_completion_code'] = in_array($this->status, [
            self::STATUS_ACCEPTED,
            self::STATUS_IN_PROGRESS,
        ], true);

        return $payload;
    }
}
