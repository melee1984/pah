<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Mobile\ResourcesController;
use App\Services\ReverseGeocodingService;
use Illuminate\Http\Request;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ResourcesControllerCityTest extends TestCase
{
    public function test_it_uses_current_delivery_coordinates_for_the_restaurant_city(): void
    {
        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')
            ->once()
            ->with(7.0731, 125.6128)
            ->andReturn('Davao City');
        $request = Request::create('/api/mobile/data/dashboard', 'GET', [
            'current_city_only' => '1',
            'latitude' => 7.0731,
            'longitude' => 125.6128,
        ]);
        $method = new ReflectionMethod(ResourcesController::class, 'restaurantCity');
        $method->setAccessible(true);

        $city = $method->invoke(
            new ResourcesController($reverseGeocoder),
            $request,
            ['latitude' => 7.0731, 'longitude' => 125.6128]
        );

        $this->assertSame('Davao City', $city);
    }
}
