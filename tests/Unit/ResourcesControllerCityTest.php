<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Mobile\ResourcesController;
use App\Services\ReverseGeocodingService;
use App\User;
use Illuminate\Http\Request;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ResourcesControllerCityTest extends TestCase
{
    public function test_selected_delivery_coordinates_enable_the_city_filter_without_a_client_flag(): void
    {
        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')
            ->once()
            ->with(6.1164, 125.1716)
            ->andReturn('General Santos City');

        $request = Request::create('/api/mobile/data/dashboard', 'GET', [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);

        $method = new ReflectionMethod(ResourcesController::class, 'restaurantCity');
        $method->setAccessible(true);

        $city = $method->invoke(
            new ResourcesController($reverseGeocoder),
            $request,
            [
                'latitude' => 6.1164,
                'longitude' => 125.1716,
            ]
        );

        $this->assertSame('General Santos City', $city);
    }

    public function test_it_uses_delivery_coordinates_instead_of_the_registered_users_city(): void
    {
        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')
            ->once()
            ->with(10.3157, 123.8854)
            ->andReturn('Cebu City');

        $request = Request::create('/api/mobile/data/dashboard', 'GET', [
            'current_city_only' => '1',
            'latitude' => 10.3157,
            'longitude' => 123.8854,
        ]);
        $request->setUserResolver(fn (string $guard) => new User(['city' => 'Davao City']));

        $method = new ReflectionMethod(ResourcesController::class, 'restaurantCity');
        $method->setAccessible(true);

        $city = $method->invoke(
            new ResourcesController($reverseGeocoder),
            $request,
            [
                'latitude' => 10.3157,
                'longitude' => 123.8854,
            ]
        );

        $this->assertSame('Cebu City', $city);
    }
}
