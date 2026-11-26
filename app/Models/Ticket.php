<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    public const STATUS_ISSUED = 'issued';
    public const STATUS_USED = 'used';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    protected $appends = ['status_label'];

    public function order()
    {
        return $this->belongsTo(TicketOrder::class, 'ticket_order_id');
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function lot()
    {
        return $this->belongsTo(TicketLot::class, 'ticket_lot_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_ISSUED => 'Emitido',
            self::STATUS_USED => 'Usado',
            self::STATUS_CANCELLED => 'Cancelado',
            default => $this->status,
        };
    }

    public static function generateQrCode(): string
    {
        return 'KNG-TKT-'.Str::upper(Str::random(16));
    }
}
