<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
        /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
            'last_latitude' => 'float',
            'last_longitude' => 'float',
            'location_updated_at' => 'datetime',
        ];
    }

    // Relacionamentos
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function agentOrders()
    {
        return $this->hasMany(Order::class, 'agent_user_id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'driver_id');
    }

    public function trackingUpdates()
    {
        return $this->hasMany(TrackingOrder::class, 'updated_by');
    }

    public function appNotifications()
    {
        return $this->hasMany(AppNotification::class);
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function savedAddresses()
    {
        return $this->hasMany(SavedAddress::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $roleName)
    {
        return $query->whereHas('role', function ($q) use ($roleName) {
            $q->where('name', $roleName);
        });
    }
}
