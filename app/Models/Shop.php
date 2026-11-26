<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    public const TYPE_RESTAURANT = 'restaurant';
    public const TYPE_MARKET = 'market';
    public const TYPE_PHARMACY = 'pharmacy';
    public const TYPE_HOTEL = 'hotel';
    public const TYPE_EVENTS = 'events';
    public const TYPE_SERVICES = 'services';

    public const TYPES = [
        self::TYPE_RESTAURANT,
        self::TYPE_MARKET,
        self::TYPE_PHARMACY,
        self::TYPE_HOTEL,
        self::TYPE_EVENTS,
        self::TYPE_SERVICES,
    ];

    public const TYPE_LABELS = [
        self::TYPE_RESTAURANT => 'Restaurante',
        self::TYPE_MARKET => 'Mercado',
        self::TYPE_PHARMACY => 'Farmácia',
        self::TYPE_HOTEL => 'Hotel',
        self::TYPE_EVENTS => 'Eventos',
        self::TYPE_SERVICES => 'Serviços',
    ];

    protected $guarded = [];

    protected $casts = [
        'is_featured' => 'boolean',
        'is_paused' => 'boolean',
        'commission_rate' => 'decimal:4',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    protected $appends = ['type_label'];

    public function setImageUrlAttribute($value): void
    {
        $this->attributes['image_url'] = MediaPath::normalize($value);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type ?? self::TYPE_RESTAURANT] ?? 'Parceiro';
    }

    public function scopeOfType($query, ?string $type)
    {
        if (! $type || ! in_array($type, self::TYPES, true)) {
            return $query;
        }

        return $query->where('type', $type);
    }

    // Relacionamentos
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function roomTypes()
    {
        return $this->hasMany(RoomType::class);
    }

    public function hotelBookings()
    {
        return $this->hasMany(HotelBooking::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function serviceOfferings()
    {
        return $this->hasMany(ServiceOffering::class);
    }

    public function serviceBookings()
    {
        return $this->hasMany(ServiceBooking::class);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user && $this->owner_user_id && (int) $this->owner_user_id === (int) $user->id;
    }
}
