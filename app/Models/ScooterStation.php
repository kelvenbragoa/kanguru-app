<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScooterStation extends Model
{
    protected $fillable = [
        'name',
        'address',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'is_active' => 'boolean',
    ];

    public function scooters(): HasMany
    {
        return $this->hasMany(Scooter::class);
    }

    public function availableScooters(): HasMany
    {
        return $this->scooters()->where('status', 'available');
    }
}
