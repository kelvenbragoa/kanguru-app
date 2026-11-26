<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function setPosterAttribute($value): void
    {
        $this->attributes['poster'] = MediaPath::normalize($value);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function lots()
    {
        return $this->hasMany(TicketLot::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function scopeActiveUpcoming($query)
    {
        return $query->where('is_active', true)
            ->where('starts_at', '>=', now()->subHours(6))
            ->orderBy('starts_at');
    }
}
