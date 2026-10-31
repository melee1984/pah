<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ReverseGeocodingService
{
    public function city($latitude, $longitude): ?string
    {
        if (! $this->validCoordinates($latitude, $longitude)) {
            Log::warning('Reverse geocoding received invalid coordinates.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return null;
        }

        $apiKey = config('services.google.maps_key');

        if (blank($apiKey)) {
            Log::error('Reverse geocoding cannot run because the Google Maps key is missing.');

            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;
        $cacheKey = sprintf('reverse_geocode_city:%.3f:%.3f', $latitude, $longitude);
        $cachedCity = Cache::get($cacheKey);

        if (is_string($cachedCity) && $cachedCity !== '') {
            Log::info('Reverse geocoding city cache hit.', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'resolved_city' => $cachedCity,
            ]);

            return $cachedCity;
        }

        try {
            $response = Http::timeout(5)->get(
                'https://maps.googleapis.com/maps/api/geocode/json',
                [
                    'latlng' => $latitude.','.$longitude,
                    'key' => $apiKey,
                ]
            );
            $responseData = $response->json();

            if (! $response->successful() || data_get($responseData, 'status') !== 'OK') {
                Log::warning('Google reverse geocoding did not return a usable result.', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'http_status' => $response->status(),
                    'google_status' => data_get($responseData, 'status'),
                    'google_error_message' => data_get($responseData, 'error_message'),
                ]);

                return null;
            }

            $city = $this->cityFromResults(data_get($responseData, 'results', []));

            if ($city !== null) {
                Cache::put($cacheKey, $city, now()->addDay());

                Log::info('Reverse geocoding city resolved.', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'resolved_city' => $city,
                ]);
            } else {
                Log::warning('Google reverse geocoding returned no city component.', [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'result_count' => count(data_get($responseData, 'results', [])),
                ]);
            }

            return $city;
        } catch (\Throwable $exception) {
            Log::warning('Reverse geocoding failed while resolving the customer city.', [
                'message' => $exception->getMessage(),
                'latitude' => $latitude,
                'longitude' => $longitude,
            ]);

            return null;
        }
    }

    private function cityFromResults(array $results): ?string
    {
        $components = collect($results)
            ->flatMap(fn (array $result) => $result['address_components'] ?? []);

        foreach (['locality', 'postal_town', 'administrative_area_level_3', 'administrative_area_level_2'] as $type) {
            $component = $components->first(
                fn (array $component) => in_array($type, $component['types'] ?? [], true)
            );
            $city = trim((string) ($component['long_name'] ?? ''));

            if ($city !== '') {
                return $city;
            }
        }

        return null;
    }

    private function validCoordinates($latitude, $longitude): bool
    {
        return is_numeric($latitude)
            && is_numeric($longitude)
            && (float) $latitude >= -90
            && (float) $latitude <= 90
            && (float) $longitude >= -180
            && (float) $longitude <= 180;
    }
}
