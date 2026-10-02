<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScooterSetting extends Model
{
    protected $fillable = [
        'unlock_fee',
        'price_per_minute',
        'minimum_amount',
    ];

    protected $casts = [
        'unlock_fee' => 'float',
        'price_per_minute' => 'float',
        'minimum_amount' => 'float',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'unlock_fee' => 10,
            'price_per_minute' => 2,
            'minimum_amount' => 20,
        ]);
    }

    public function quote(int $minutes): array
    {
        $minutes = max(0, $minutes);
        $amount = max(
            (float) $this->minimum_amount,
            (float) $this->unlock_fee + ($minutes * (float) $this->price_per_minute),
        );

        return [
            'unlock_fee' => (float) $this->unlock_fee,
            'price_per_minute' => (float) $this->price_per_minute,
            'minimum_amount' => (float) $this->minimum_amount,
            'duration_minutes' => $minutes,
            'amount' => round($amount, 2),
        ];
    }
}
