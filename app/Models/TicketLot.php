<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketLot extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'sold' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['remaining'];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getRemainingAttribute(): int
    {
        return max(0, (int) $this->quantity - (int) $this->sold);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
