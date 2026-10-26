<?php

namespace Tests\Feature\Api;

use App\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_only_active_cities_in_alphabetical_order(): void
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

    public function test_it_returns_an_empty_list_when_no_city_is_active(): void
    {
        City::query()->create(['name' => 'Inactive City', 'active' => false]);

        $this->getJson(route('api.cities.index'))
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonCount(0, 'cities');
    }
}
