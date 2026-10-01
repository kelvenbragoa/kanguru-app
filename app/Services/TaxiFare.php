<?php

namespace App\Services;

class TaxiFare
{
    public const BASE = 50;

    public const PER_KM = 25;

    public const MINIMUM = 80;

    public static function quote(float $originLat, float $originLng, float $destinationLat, float $destinationLng): array
    {
        $km = self::distanceKm($originLat, $originLng, $destinationLat, $destinationLng);
        $amount = max(self::MINIMUM, round(self::BASE + (self::PER_KM * $km), 2));

        return [
            'distance_km' => round($km, 2),
            'amount' => $amount,
            'currency' => 'MZN',
            'base' => self::BASE,
            'per_km' => self::PER_KM,
            'minimum' => self::MINIMUM,
        ];
    }

    public static function distanceKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
