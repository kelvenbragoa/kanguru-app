<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelBooking extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_CHECKED_IN = 'checked_in';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_NO_SHOW = 'no_show';

    public const ACTIVE_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_CONFIRMED,
        self::STATUS_CHECKED_IN,
    ];

    protected $guarded = [];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'guests' => 'integer',
        'nights' => 'integer',
        'price_per_night' => 'decimal:2',
        'total_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'confirm_deadline_at' => 'datetime',
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'no_show_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'customer_status_label', 'qr_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendente',
            self::STATUS_CONFIRMED => 'Confirmada',
            self::STATUS_CHECKED_IN => 'Em estadia',
            self::STATUS_COMPLETED => 'Concluída',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_NO_SHOW => 'No-show',
            default => $this->status,
        };
    }

    public function getCustomerStatusLabelAttribute(): string
    {
        return $this->status_label;
    }

    public function getQrUrlAttribute(): ?string
    {
        $payload = $this->qr_payload ?: $this->checkin_code;
        if (! $payload) {
            return null;
        }

        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data='.urlencode($payload);
    }

    public static function generateCode(): string
    {
        return 'HTL-'.now()->format('ymd').'-'.random_int(1000, 9999);
    }

    public static function generateCheckinCode(): string
    {
        return str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    public static function generateQrPayload(string $code, string $checkinCode): string
    {
        return $code.'|'.$checkinCode;
    }

    public function canCancel(): bool
    {
        if (! in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED], true)) {
            return false;
        }

        if ($this->status === self::STATUS_PENDING) {
            return true;
        }

        $freeDays = (int) ($this->roomType?->free_cancel_days ?? 1);
        $deadline = $this->check_in->copy()->subDays($freeDays)->startOfDay();

        return now()->startOfDay()->lte($deadline);
    }
}
