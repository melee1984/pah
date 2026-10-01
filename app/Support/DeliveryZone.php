<?php

namespace App\Support;

class DeliveryZone
{
    public function maximumDistanceKilometers(): float
    {
        return max(0, (float) config('services.delivery.max_distance_km', 10));
    }

    /**
     * Find the closest location with valid coordinates.
     *
     * @return array{location: object, distance_km: float}|null
     */
    public function closestLocation(iterable $locations, float $userLatitude, float $userLongitude): ?array
    {
        if (! $this->coordinatesAreValid($userLatitude, $userLongitude)) {
            return null;
        }

        $closest = null;

        foreach ($locations as $location) {
            $latitude = $location->latitude ?? null;
            $longitude = $location->longtitude ?? null;

            if (! is_numeric($latitude)
                || ! is_numeric($longitude)
                || ! $this->coordinatesAreValid($latitude, $longitude)) {
                continue;
            }

            $distance = $this->distanceKilometers(
                $userLatitude,
                $userLongitude,
                (float) $latitude,
                (float) $longitude,
            );

            if ($closest === null || $distance < $closest['distance_km']) {
                $closest = [
                    'location' => $location,
                    'distance_km' => $distance,
                ];
            }
        }

        return $closest;
    }

    public function isWithinRange(float $distanceKilometers): bool
    {
        return $distanceKilometers <= $this->maximumDistanceKilometers();
    }

    public function distanceKilometers(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): float {
        $earthRadiusKilometers = 6371;
        $latitudeDifference = deg2rad($toLatitude - $fromLatitude);
        $longitudeDifference = deg2rad($toLongitude - $fromLongitude);
        $fromLatitudeRadians = deg2rad($fromLatitude);
        $toLatitudeRadians = deg2rad($toLatitude);

        $haversine = sin($latitudeDifference / 2) ** 2
            + cos($fromLatitudeRadians)
            * cos($toLatitudeRadians)
            * sin($longitudeDifference / 2) ** 2;

        return 2 * $earthRadiusKilometers * asin(min(1, sqrt($haversine)));
    }

    public function coordinatesAreValid(mixed $latitude, mixed $longitude): bool
    {
        return is_numeric($latitude)
            && is_numeric($longitude)
            && (float) $latitude >= -90
            && (float) $latitude <= 90
            && (float) $longitude >= -180
            && (float) $longitude <= 180;
    }
}
