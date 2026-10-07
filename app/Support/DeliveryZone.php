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

    /**
     * Prefer the closest deliverable branch. If none qualifies, return the
     * closest branch and its failure reason so the API can explain the block.
     *
     * @return array{location: object, distance_km: float, check: array}|null
     */
    public function bestDeliveryLocation(iterable $locations, float $userLatitude, float $userLongitude): ?array
    {
        if (! $this->coordinatesAreValid($userLatitude, $userLongitude)) {
            return null;
        }

        $closest = null;
        $closestAllowed = null;

        foreach ($locations as $location) {
            if (! $this->coordinatesAreValid($location->latitude ?? null, $location->longtitude ?? null)) {
                continue;
            }

            $check = $this->check(
                (float) $location->latitude,
                (float) $location->longtitude,
                $userLatitude,
                $userLongitude,
            );
            $candidate = [
                'location' => $location,
                'distance_km' => $check['distance_km'],
                'check' => $check,
            ];

            if ($closest === null || $candidate['distance_km'] < $closest['distance_km']) {
                $closest = $candidate;
            }

            if ($check['allowed']
                && ($closestAllowed === null || $candidate['distance_km'] < $closestAllowed['distance_km'])) {
                $closestAllowed = $candidate;
            }
        }

        return $closestAllowed ?? $closest;
    }

    public function isWithinRange(float $distanceKilometers): bool
    {
        return $distanceKilometers <= $this->maximumDistanceKilometers();
    }

    /**
     * Check both the configured service-area boundary and maximum distance.
     *
     * @return array{allowed: bool, reason: string|null, distance_km: float, merchant_area: array|null, customer_area: array|null}
     */
    public function check(
        float $merchantLatitude,
        float $merchantLongitude,
        float $customerLatitude,
        float $customerLongitude,
    ): array {
        $distance = $this->distanceKilometers(
            $merchantLatitude,
            $merchantLongitude,
            $customerLatitude,
            $customerLongitude,
        );
        $merchantArea = $this->areaAt($merchantLatitude, $merchantLongitude);
        $customerArea = $this->areaAt($customerLatitude, $customerLongitude);

        $crossesBoundary = config('delivery_zones.enabled', true)
            && ($merchantArea['key'] ?? null) !== ($customerArea['key'] ?? null)
            && ($merchantArea !== null || $customerArea !== null);

        return [
            'allowed' => ! $crossesBoundary && $this->isWithinRange($distance),
            'reason' => $crossesBoundary
                ? 'service_area'
                : ($this->isWithinRange($distance) ? null : 'distance'),
            'distance_km' => $distance,
            'merchant_area' => $merchantArea,
            'customer_area' => $customerArea,
        ];
    }

    /**
     * Resolve an automatically configured separated service area.
     *
     * @return array{key: string, label: string}|null
     */
    public function areaAt(mixed $latitude, mixed $longitude): ?array
    {
        if (! $this->coordinatesAreValid($latitude, $longitude)) {
            return null;
        }

        foreach (config('delivery_zones.areas', []) as $key => $area) {
            foreach ($area['polygons'] ?? [] as $polygon) {
                if ($this->pointIsInsidePolygon((float) $latitude, (float) $longitude, $polygon)) {
                    return [
                        'key' => (string) $key,
                        'label' => (string) ($area['label'] ?? $key),
                    ];
                }
            }
        }

        return null;
    }

    public function areaLabel(mixed $latitude, mixed $longitude): string
    {
        return $this->areaAt($latitude, $longitude)['label']
            ?? (string) config('delivery_zones.default_label', 'Standard delivery area');
    }

    /** @param array{reason: string|null, distance_km: float, merchant_area: array|null, customer_area: array|null} $check */
    public function failureResponse(array $check): array
    {
        if ($check['reason'] === 'service_area') {
            $merchantArea = $check['merchant_area']['label'] ?? config('delivery_zones.default_label');
            $customerArea = $check['customer_area']['label'] ?? config('delivery_zones.default_label');

            return [
                'status' => 0,
                'message' => "This merchant is in {$merchantArea}, but your address is in {$customerArea}. Please choose a merchant in the same delivery area as your address.",
                'reason' => 'service_area',
                'merchant_area' => $merchantArea,
                'customer_area' => $customerArea,
            ];
        }

        $maximumDistance = $this->maximumDistanceKilometers();

        return [
            'status' => 0,
            'message' => sprintf(
                'This order is not allowed because your location is %.2f km from the merchant. The maximum delivery distance is %s km.',
                $check['distance_km'],
                rtrim(rtrim(number_format($maximumDistance, 2, '.', ''), '0'), '.'),
            ),
            'distance_km' => round($check['distance_km'], 2),
            'max_distance_km' => $maximumDistance,
            'reason' => 'distance',
        ];
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

    /** @param array<int, array{0: float|int, 1: float|int}> $polygon */
    private function pointIsInsidePolygon(float $latitude, float $longitude, array $polygon): bool
    {
        if (count($polygon) < 3) {
            return false;
        }

        $inside = false;
        $last = count($polygon) - 1;

        for ($current = 0; $current < count($polygon); $current++) {
            [$currentLatitude, $currentLongitude] = $polygon[$current];
            [$lastLatitude, $lastLongitude] = $polygon[$last];

            $intersects = (($currentLatitude > $latitude) !== ($lastLatitude > $latitude))
                && ($longitude < ($lastLongitude - $currentLongitude)
                    * ($latitude - $currentLatitude)
                    / ($lastLatitude - $currentLatitude)
                    + $currentLongitude);

            if ($intersects) {
                $inside = ! $inside;
            }

            $last = $current;
        }

        return $inside;
    }
}
