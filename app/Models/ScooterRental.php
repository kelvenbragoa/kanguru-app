<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScooterRental extends Model
{
    protected $fillable = [
        'code',
        'user_id',
        'scooter_id',
        'start_station_id',
        'end_station_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'unlock_fee',
        'price_per_minute',
        'minimum_amount',
        'amount',
        'payment_method',
        'payer_phone',
        'payment_status',
        'status',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'unlock_fee' => 'float',
        'price_per_minute' => 'float',
        'minimum_amount' => 'float',
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scooter(): BelongsTo
    {
        return $this->belongsTo(Scooter::class);
    }

    public function startStation(): BelongsTo
    {
        return $this->belongsTo(ScooterStation::class, 'start_station_id');
    }

    public function endStation(): BelongsTo
    {
        return $this->belongsTo(ScooterStation::class, 'end_station_id');
    }

    public function elapsedMinutes(?\DateTimeInterface $at = null): int
    {
        $end = $at ?? now();
        $seconds = max(0, $this->started_at->diffInSeconds($end));

        return (int) ceil($seconds / 60);
    }

    public function liveAmount(): array
    {
        $settings = new ScooterSetting([
            'unlock_fee' => $this->unlock_fee,
            'price_per_minute' => $this->price_per_minute,
            'minimum_amount' => $this->minimum_amount,
        ]);

        return $settings->quote($this->elapsedMinutes());
    }
}
