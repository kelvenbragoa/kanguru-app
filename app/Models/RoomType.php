<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class RoomType extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price_per_night' => 'decimal:2',
        'capacity' => 'integer',
        'quantity' => 'integer',
        'is_active' => 'boolean',
        'free_cancel_days' => 'integer',
        'deposit_percent' => 'integer',
    ];

    public function dateBlocks()
    {
        return $this->hasMany(RoomDateBlock::class);
    }

    public function setImageAttribute($value): void
    {
        $this->attributes['image'] = MediaPath::normalize($value);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function bookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
