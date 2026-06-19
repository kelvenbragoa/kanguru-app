<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'total_price' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'weight' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_user_id');
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function orderType()
    {
        return $this->belongsTo(OrderType::class);
    }

    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class);
    }

    public function originLocation()
    {
        return $this->belongsTo(Location::class, 'origin_location_id');
    }

    public function destinationLocation()
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function trackingOrders()
    {
        return $this->hasMany(TrackingOrder::class);
    }

    // Scopes
    public function scopeByStatus($query, $statusName)
    {
        return $query->whereHas('orderStatus', function ($q) use ($statusName) {
            $q->where('name', $statusName);
        });
    }

    public function scopeByType($query, $typeName)
    {
        return $query->whereHas('orderType', function ($q) use ($typeName) {
            $q->where('name', $typeName);
        });
    }

    public function scopePending($query)
    {
        return $query->byStatus('pending');
    }

    public function scopeInProgress($query)
    {
        return $query->whereHas('orderStatus', function ($q) {
            $q->where('is_final', false);
        });
    }

    // Métodos auxiliares
    public function getTotalWeight()
    {
        return $this->orderItems()->sum(DB::raw('quantity * (SELECT weight FROM products WHERE id = order_items.product_id)'));
    }
}
