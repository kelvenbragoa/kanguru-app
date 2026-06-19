<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleStatus extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_available' => 'boolean',
    ];

    // Relacionamentos
    public function vehicles()
    {
        return $this->hasMany(Vehicle::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
