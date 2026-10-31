<?php

namespace Tests\Feature\Api;

use App\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_city_endpoint_returns_only_active_cities(): void
    {
        $zamboanga = City::query()->create(['name' => 'Zamboanga City', 'active' => true]);
        City::query()->create(['name' => 'Inactive City', 'active' => false]);
        $davao = City::query()->create(['name' => 'Davao City', 'active' => true]);

        $this->getJson(route('api.cities.index'))
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonCount(2, 'cities')
            ->assertExactJson([
                'status' => 1,
                'cities' => [
                    ['id' => $davao->id, 'name' => 'Davao City'],
                    ['id' => $zamboanga->id, 'name' => 'Zamboanga City'],
                ],
            ]);
    }

    public function test_address_city_endpoint_returns_all_cities_with_service_status(): void
    {
        $active = City::query()->create(['name' => 'Davao City', 'active' => true]);
        $inactive = City::query()->create(['name' => 'Inactive City', 'active' => false]);

        $this->getJson(route('api.address-cities.index'))
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertExactJson([
                'status' => 1,
                'cities' => [
                    ['id' => $active->id, 'name' => 'Davao City', 'active' => true],
                    ['id' => $inactive->id, 'name' => 'Inactive City', 'active' => false],
                ],
            ]);
    }
}
