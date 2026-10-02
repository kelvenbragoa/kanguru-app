<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Scooter extends Model
{
    protected $fillable = [
        'code',
        'scooter_station_id',
        'battery_percent',
        'status',
    ];

    protected $casts = [
        'battery_percent' => 'integer',
    ];

    public function station(): BelongsTo
    {
        return $this->belongsTo(ScooterStation::class, 'scooter_station_id');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(ScooterRental::class);
    }
}
