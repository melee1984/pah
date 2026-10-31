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
