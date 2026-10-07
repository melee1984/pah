<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiderBatchingService
{
    private const TERMINAL_STATES = ['delivered', 'cancelled', 'failed'];

    private const PICKUP_PENDING_STATES = [
        'offered',
        'accepted',
        'going_to_merchant',
        'arrived_at_merchant',
        'order_not_ready',
        'waiting_started',
        'pickup_verified',
    ];

    public function enabled(): bool
    {
        return (bool) config('rider.batching_enabled', true);
    }

    public function maxActiveDeliveries(): int
    {
        return $this->enabled()
            ? min(2, max(1, (int) config('rider.batch_max_active_deliveries', 2)))
            : 1;
    }

    public function activeDeliveries(int $riderId, ?int $exceptDeliveryId = null): Collection
    {
        return DB::table('rider_api_deliveries')
            ->where('rider_id', $riderId)
            ->whereNotIn('current_state', self::TERMINAL_STATES)
            ->when($exceptDeliveryId, fn ($query) => $query->where('id', '!=', $exceptDeliveryId))
            ->orderBy('accepted_at')
            ->orderBy('id')
            ->get();
    }

    public function activeCount(int $riderId, ?int $exceptDeliveryId = null): int
    {
        return $this->activeDeliveries($riderId, $exceptDeliveryId)->count();
    }

    /**
     * Returns the route impact for an add-on delivery, or null when it is not
     * sufficiently close to the rider's remaining route.
     *
     * @return array<string, mixed>|null
     */
    public function addOnPlan(int $riderId, object $candidate): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $active = $this->activeDeliveries($riderId, isset($candidate->id) ? (int) $candidate->id : null);
        if ($active->isEmpty() || $active->count() >= $this->maxActiveDeliveries()) {
            return null;
        }

        $origin = $this->latestFreshLocation($riderId);
        if (! $origin || ! $this->hasCoordinates($candidate, 'pickup') || ! $this->hasCoordinates($candidate, 'dropoff')) {
            return null;
        }

        $baselineStops = $this->stopsForDeliveries($active);
        $combinedStops = $this->stopsForDeliveries($active->concat([$candidate]));
        if ($baselineStops === [] || $combinedStops === []) {
            return null;
        }

        $baseline = $this->shortestValidRoute($origin, $baselineStops);
        $combined = $this->shortestValidRoute($origin, $combinedStops);
        if (! $baseline || ! $combined) {
            return null;
        }

        $candidatePickupDetour = $this->pickupInsertionDetour($origin, $baseline['stops'], $candidate);
        $existingDelay = $this->existingDeliveryDelayMeters($origin, $baseline, $combined, $active);
        $addedDistance = max(0, $combined['distance_meters'] - $baseline['distance_meters']);
        $speedMetersPerSecond = max(1, (float) config('rider.batch_average_speed_kph', 25) * 1000 / 3600);
        $addedSeconds = (int) round($addedDistance / $speedMetersPerSecond);
        $existingDelaySeconds = (int) round($existingDelay / $speedMetersPerSecond);

        if ($candidatePickupDetour > (float) config('rider.batch_max_pickup_detour_km', 2) * 1000
            || $addedDistance > (float) config('rider.batch_max_added_distance_km', 8) * 1000
            || $existingDelaySeconds > (int) config('rider.batch_max_existing_delay_minutes', 10) * 60) {
            return null;
        }

        return [
            'is_add_on' => true,
            'active_order_count' => $active->count(),
            'resulting_order_count' => $active->count() + 1,
            'pickup_detour_meters' => (int) round($candidatePickupDetour),
            'added_distance_meters' => $addedDistance,
            'added_eta_seconds' => $addedSeconds,
            'existing_order_delay_seconds' => $existingDelaySeconds,
            'stops' => $combined['stops'],
        ];
    }

    /** @return array<string, mixed> */
    public function routeForRider(int $riderId): array
    {
        $origin = $this->latestFreshLocation($riderId, false);
        $deliveries = $this->activeDeliveries($riderId);
        $stops = $this->stopsForDeliveries($deliveries);
        $route = $origin && $stops !== [] ? $this->shortestValidRoute($origin, $stops) : null;

        return [
            'origin' => $origin,
            'order_count' => $deliveries->count(),
            'is_batched' => $deliveries->count() > 1,
            'distance_meters' => $route['distance_meters'] ?? null,
            'eta_seconds' => isset($route['distance_meters'])
                ? (int) round($route['distance_meters'] / max(1, (float) config('rider.batch_average_speed_kph', 25) * 1000 / 3600))
                : null,
            'stops' => $route['stops'] ?? $stops,
        ];
    }

    public function markActiveBatch(int $riderId): void
    {
        $activeIds = $this->activeDeliveries($riderId)->pluck('id');
        $count = $activeIds->count();
        if ($count === 0) {
            return;
        }

        DB::table('rider_api_deliveries')->whereIn('id', $activeIds)->update([
            'order_count' => $count,
            'is_batched' => $count > 1,
            'updated_at' => now(),
        ]);
    }

    /** @return array{latitude: float, longitude: float, recorded_at: mixed}|null */
    private function latestFreshLocation(int $riderId, bool $enforceFreshness = true): ?array
    {
        $location = DB::table('rider_api_locations')
            ->where('rider_id', $riderId)
            ->orderByDesc('recorded_at')
            ->orderByDesc('id')
            ->first();

        if (! $location) {
            return null;
        }
        if ($enforceFreshness && now()->subMinutes(max(1, (int) config('rider.offer_location_max_age_minutes', 5)))
            ->greaterThan($location->recorded_at)) {
            return null;
        }

        return [
            'latitude' => (float) $location->latitude,
            'longitude' => (float) $location->longitude,
            'recorded_at' => $location->recorded_at,
        ];
    }

    /** @return list<array<string, mixed>> */
    private function stopsForDeliveries(Collection $deliveries): array
    {
        $stops = [];
        foreach ($deliveries as $delivery) {
            if (in_array($delivery->current_state, self::PICKUP_PENDING_STATES, true)
                && $this->hasCoordinates($delivery, 'pickup')) {
                $stops[] = $this->stop($delivery, 'pickup');
            }
            if ($this->hasCoordinates($delivery, 'dropoff')) {
                $stops[] = $this->stop($delivery, 'dropoff');
            }
        }

        return $stops;
    }

    /** @return array<string, mixed> */
    private function stop(object $delivery, string $type): array
    {
        return [
            'delivery_id' => $delivery->reference,
            'legacy_order_id' => $delivery->legacy_order_id ?? null,
            'type' => $type,
            'latitude' => (float) $delivery->{$type.'_latitude'},
            'longitude' => (float) $delivery->{$type.'_longitude'},
            'address' => $delivery->{$type.'_address'},
            'area' => $delivery->{$type.'_area'},
            'merchant_name' => $delivery->merchant_name,
        ];
    }

    private function hasCoordinates(object $delivery, string $type): bool
    {
        return is_numeric($delivery->{$type.'_latitude'} ?? null)
            && is_numeric($delivery->{$type.'_longitude'} ?? null);
    }

    /** @return array{distance_meters: int, stops: list<array<string, mixed>>}|null */
    private function shortestValidRoute(array $origin, array $stops): ?array
    {
        $best = null;
        foreach ($this->permutations($stops) as $permutation) {
            if (! $this->pickupPrecedesDropoff($permutation)) {
                continue;
            }
            $distance = $this->routeDistance($origin, $permutation);
            if ($best === null || $distance < $best['distance_meters']) {
                $best = ['distance_meters' => $distance, 'stops' => array_values($permutation)];
            }
        }

        return $best;
    }

    private function pickupPrecedesDropoff(array $stops): bool
    {
        $picked = [];
        $requiresPickup = collect($stops)->where('type', 'pickup')->pluck('delivery_id')->all();
        foreach ($stops as $stop) {
            if ($stop['type'] === 'pickup') {
                $picked[$stop['delivery_id']] = true;
            } elseif (in_array($stop['delivery_id'], $requiresPickup, true) && ! isset($picked[$stop['delivery_id']])) {
                return false;
            }
        }

        return true;
    }

    /** @return list<array<int, array<string, mixed>>> */
    private function permutations(array $items): array
    {
        if (count($items) <= 1) {
            return [$items];
        }

        $result = [];
        foreach ($items as $index => $item) {
            $remaining = $items;
            array_splice($remaining, $index, 1);
            foreach ($this->permutations($remaining) as $permutation) {
                array_unshift($permutation, $item);
                $result[] = $permutation;
            }
        }

        return $result;
    }

    private function routeDistance(array $origin, array $stops): int
    {
        $distance = 0;
        $from = $origin;
        foreach ($stops as $stop) {
            $distance += $this->distanceMeters(
                $from['latitude'],
                $from['longitude'],
                $stop['latitude'],
                $stop['longitude'],
            );
            $from = $stop;
        }

        return $distance;
    }

    private function pickupInsertionDetour(array $origin, array $baselineStops, object $candidate): int
    {
        $pickup = [
            'latitude' => (float) $candidate->pickup_latitude,
            'longitude' => (float) $candidate->pickup_longitude,
        ];
        $best = PHP_INT_MAX;
        $from = $origin;
        foreach ($baselineStops as $to) {
            $detour = $this->distanceMeters($from['latitude'], $from['longitude'], $pickup['latitude'], $pickup['longitude'])
                + $this->distanceMeters($pickup['latitude'], $pickup['longitude'], $to['latitude'], $to['longitude'])
                - $this->distanceMeters($from['latitude'], $from['longitude'], $to['latitude'], $to['longitude']);
            $best = min($best, max(0, $detour));
            $from = $to;
        }

        return $best === PHP_INT_MAX ? 0 : $best;
    }

    private function existingDeliveryDelayMeters(array $origin, array $baseline, array $combined, Collection $active): int
    {
        $activeIds = $active->pluck('reference')->all();
        $baselineDistance = $this->distanceUntilLastDropoff($origin, $baseline['stops'], $activeIds);
        $combinedDistance = $this->distanceUntilLastDropoff($origin, $combined['stops'], $activeIds);

        return max(0, $combinedDistance - $baselineDistance);
    }

    private function distanceUntilLastDropoff(array $origin, array $stops, array $deliveryIds): int
    {
        if ($stops === []) {
            return 0;
        }
        $distance = 0;
        $lastDropoffDistance = 0;
        foreach ($stops as $stop) {
            $distance += $this->distanceMeters(
                $origin['latitude'],
                $origin['longitude'],
                $stop['latitude'],
                $stop['longitude'],
            );
            if ($stop['type'] === 'dropoff' && in_array($stop['delivery_id'], $deliveryIds, true)) {
                $lastDropoffDistance = $distance;
            }
            $origin = $stop;
        }

        return $lastDropoffDistance;
    }

    private function distanceMeters(mixed $fromLat, mixed $fromLng, mixed $toLat, mixed $toLng): int
    {
        $fromLat = deg2rad((float) $fromLat);
        $toLat = deg2rad((float) $toLat);
        $latDelta = $toLat - $fromLat;
        $lngDelta = deg2rad((float) $toLng - (float) $fromLng);
        $value = sin($latDelta / 2) ** 2
            + cos($fromLat) * cos($toLat) * sin($lngDelta / 2) ** 2;

        return (int) round(6371000 * 2 * atan2(sqrt($value), sqrt(1 - $value)));
    }
}
