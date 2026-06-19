<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $guarded = [];

    protected $casts = [
        'capacity' => 'decimal:2',
        'year' => 'integer',
    ];

    // Relacionamentos
    public function vehicleType()
    {
        return $this->belongsTo(VehicleType::class);
    }

    public function vehicleStatus()
    {
        return $this->belongsTo(VehicleStatus::class);
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // Scopes
    public function scopeAvailable($query)
    {
        return $query->whereHas('vehicleStatus', function ($q) {
            $q->where('is_available', true);
        });
    }

    public function scopeByType($query, $typeName)
    {
        return $query->whereHas('vehicleType', function ($q) use ($typeName) {
            $q->where('name', $typeName);
        });
    }
}
