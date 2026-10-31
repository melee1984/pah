<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Mobile\ResourcesController;
use App\Services\ReverseGeocodingService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ResourcesControllerCityTest extends TestCase
{
    public function test_it_uses_the_local_delivery_zone_when_google_cannot_resolve_the_city(): void
    {
        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')
            ->once()
            ->with(7.1132673, 125.4927311)
            ->andReturnNull();

        $request = Request::create('/api/mobile/data/dashboard', 'GET', [
            'latitude' => 7.1132673,
            'longitude' => 125.4927311,
        ]);

        $this->assertSame(
            'Davao City',
            $this->restaurantCity($reverseGeocoder, $request, [
                'latitude' => 7.1132673,
                'longitude' => 125.4927311,
            ])
        );
    }

    public function test_it_uses_the_latest_default_delivery_address_before_the_profile_city(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('active')->default(true);
            $table->boolean('default')->default(false);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        DB::table('user_address')->insert([
            'user_id' => 25,
            'active' => true,
            'default' => true,
            'lat' => 7.1132673,
            'long' => 125.4927311,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')
            ->once()
            ->with(6.1164, 125.1716)
            ->andReturnNull();

        $user = (new User(['city' => 'Cebu City']))->forceFill(['id' => 25]);
        $request = Request::create('/api/mobile/data/dashboard', 'GET', [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);
        $request->setUserResolver(fn () => $user);

        $this->assertSame(
            'Davao City',
            $this->restaurantCity($reverseGeocoder, $request, [
                'latitude' => 6.1164,
                'longitude' => 125.1716,
            ])
        );
    }

    public function test_it_uses_the_authenticated_users_city_when_other_sources_are_unavailable(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        $reverseGeocoder = Mockery::mock(ReverseGeocodingService::class);
        $reverseGeocoder->shouldReceive('city')
            ->once()
            ->with(6.1164, 125.1716)
            ->andReturnNull();

        $user = (new User(['city' => 'Davao City']))->forceFill(['id' => 25]);
        $request = Request::create('/api/mobile/data/dashboard', 'GET', [
            'latitude' => 6.1164,
            'longitude' => 125.1716,
        ]);
        $request->setUserResolver(fn () => $user);

        $this->assertSame(
            'Davao City',
            $this->restaurantCity($reverseGeocoder, $request, [
                'latitude' => 6.1164,
                'longitude' => 125.1716,
            ])
        );
    }

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

    private function restaurantCity(
        ReverseGeocodingService $reverseGeocoder,
        Request $request,
        array $validated
    ): ?string {
        $method = new ReflectionMethod(ResourcesController::class, 'restaurantCity');
        $method->setAccessible(true);

        return $method->invoke(
            new ResourcesController($reverseGeocoder),
            $request,
            $validated
        );
    }
}
