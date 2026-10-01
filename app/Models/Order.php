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
        'current_latitude' => 'float',
        'current_longitude' => 'float',
        'origin_latitude' => 'float',
        'origin_longitude' => 'float',
        'destination_latitude' => 'float',
        'destination_longitude' => 'float',
        'scheduled_at' => 'datetime',
        'collected_at' => 'datetime',
        'delivered_at' => 'datetime',
        'location_updated_at' => 'datetime',
    ];

    protected $appends = ['customer_status_label'];

    public function getCustomerStatusLabelAttribute(): string
    {
        $this->loadMissing(['orderType', 'orderStatus']);
        $status = $this->orderStatus?->name;

        if ($this->orderType?->name === 'Táxi') {
            return match ($status) {
                'pending', 'confirmed' => 'À procura de táxi',
                'assigned' => 'Táxi a caminho',
                'collecting' => 'A caminho do passageiro',
                'collected', 'in_transit', 'delivering' => 'Em viagem',
                'delivered' => 'Viagem concluída',
                'cancelled' => 'Cancelada',
                'failed' => 'Falhou',
                default => $this->orderStatus?->display_name ?? 'Pedido',
            };
        }

        return $this->orderStatus?->display_name ?? 'Pedido';
    }

    public function liveLocation(): ?array
    {
        if ($this->current_latitude !== null && $this->current_longitude !== null) {
            return [
                'latitude' => (float) $this->current_latitude,
                'longitude' => (float) $this->current_longitude,
                'updated_at' => $this->location_updated_at,
            ];
        }

        $last = $this->relationLoaded('trackingOrders')
            ? $this->trackingOrders
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->sortByDesc('created_at')
                ->first()
            : $this->trackingOrders()
                ->whereNotNull('latitude')
                ->whereNotNull('longitude')
                ->latest()
                ->first();

        if (! $last) {
            return null;
        }

        return [
            'latitude' => (float) $last->latitude,
            'longitude' => (float) $last->longitude,
            'updated_at' => $last->created_at,
        ];
    }

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
