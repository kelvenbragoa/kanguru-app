<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketOrder extends Model
{
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function lot()
    {
        return $this->belongsTo(TicketLot::class, 'ticket_lot_id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public static function generateCode(): string
    {
        return 'EVT-'.now()->format('ymd').'-'.random_int(1000, 9999);
    }
}
