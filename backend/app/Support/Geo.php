<?php

namespace App\Support;

class Geo
{
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Distancia en metros entre dos coordenadas (fórmula de Haversine).
     */
    public static function distanceInMeters(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $deltaLatitude = deg2rad($latitudeB - $latitudeA);
        $deltaLongitude = deg2rad($longitudeB - $longitudeA);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($latitudeA)) * cos(deg2rad($latitudeB)) * sin($deltaLongitude / 2) ** 2;

        return self::EARTH_RADIUS_METERS * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
