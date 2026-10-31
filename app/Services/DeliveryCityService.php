<?php

namespace App\Services;

use App\PartnerLocation;

class DeliveryCityService
{
    public function __construct(private readonly ReverseGeocodingService $reverseGeocoder) {}

    public function city($latitude, $longitude): ?string
    {
        return $this->reverseGeocoder->city($latitude, $longitude);
    }

    public function check(PartnerLocation $location, $latitude, $longitude): array
    {
        $deliveryCity = $this->city($latitude, $longitude);
        $partnerCity = trim((string) $location->city);

        if ($deliveryCity === null) {
            return [
                'allowed' => false,
                'reason' => 'city_unresolved',
                'delivery_city' => null,
                'partner_city' => $partnerCity ?: null,
            ];
        }

        return [
            'allowed' => $partnerCity !== '' && $this->matches($partnerCity, $deliveryCity),
            'reason' => $partnerCity === '' || ! $this->matches($partnerCity, $deliveryCity)
                ? 'city_mismatch'
                : null,
            'delivery_city' => $deliveryCity,
            'partner_city' => $partnerCity ?: null,
        ];
    }

    public function matches(?string $firstCity, ?string $secondCity): bool
    {
        $firstCity = $this->normalize($firstCity);
        $secondCity = $this->normalize($secondCity);

        return $firstCity !== '' && $firstCity === $secondCity;
    }

    public function failureResponse(array $check): array
    {
        if ($check['reason'] === 'city_unresolved') {
            return [
                'status' => 0,
                'message' => 'We could not determine the city for this delivery location. Please adjust the map pin and try again.',
                'reason' => 'city_unresolved',
            ];
        }

        return [
            'status' => 0,
            'message' => sprintf(
                'This restaurant is in %s, but your delivery address is in %s. Please choose a restaurant in the same city as your delivery address.',
                $check['partner_city'] ?: 'another city',
                $check['delivery_city'] ?: 'another city',
            ),
            'reason' => 'city_mismatch',
            'partner_city' => $check['partner_city'],
            'delivery_city' => $check['delivery_city'],
        ];
    }

    private function normalize(?string $city): string
    {
        $city = mb_strtolower(trim((string) $city));
        $city = preg_replace('/^city\s+of\s+/u', '', $city) ?: $city;
        $city = preg_replace('/\s+city$/u', '', $city) ?: $city;

        return trim(preg_replace('/\s+/u', ' ', $city) ?: $city);
    }
}
