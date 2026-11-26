<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class ServiceOffering extends Model
{
    public const LOCATION_ONSITE = 'onsite';
    public const LOCATION_REMOTE = 'remote';
    public const LOCATION_BOTH = 'both';

    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    protected $appends = ['location_label'];

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
        return $this->hasMany(ServiceBooking::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getLocationLabelAttribute(): string
    {
        return match ($this->location_type) {
            self::LOCATION_REMOTE => 'À distância',
            self::LOCATION_BOTH => 'No local ou à distância',
            default => 'No local',
        };
    }
}
