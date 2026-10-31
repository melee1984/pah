<?php

namespace Tests\Unit;

use App\Services\ReverseGeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ReverseGeocodingServiceTest extends TestCase
{
    public function test_it_resolves_and_caches_a_city_from_coordinates(): void
    {
        config()->set('services.google.maps_key', 'test-key');
        Cache::flush();
        Http::fake([
            'maps.googleapis.com/*' => Http::response([
                'status' => 'OK',
                'results' => [[
                    'address_components' => [
                        ['long_name' => 'Davao del Sur', 'types' => ['administrative_area_level_2']],
                        ['long_name' => 'Davao City', 'types' => ['locality']],
                    ],
                ]],
            ]),
        ]);

        $service = app(ReverseGeocodingService::class);

        $this->assertSame('Davao City', $service->city(7.0731, 125.6128));
        $this->assertSame('Davao City', $service->city(7.0731, 125.6128));

        Http::assertSentCount(1);
    }

    public function test_it_returns_null_for_invalid_coordinates_without_making_a_request(): void
    {
        config()->set('services.google.maps_key', 'test-key');
        Http::fake();

        $this->assertNull(app(ReverseGeocodingService::class)->city(200, 125.6128));

        Http::assertNothingSent();
    }
}
