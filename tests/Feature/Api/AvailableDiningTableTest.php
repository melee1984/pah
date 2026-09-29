<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AvailableDiningTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('address_1')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_it_only_returns_active_available_tables_for_the_location(): void
    {
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'address_1' => 'Downtown branch',
            'active' => true,
        ]);

        $otherLocationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'active' => true,
        ]);

        DB::table('partner_location_tables')->insert([
            ['partner_location_id' => $locationId, 'name' => 'Table 2', 'capacity' => 4, 'active' => true, 'is_available' => true],
            ['partner_location_id' => $locationId, 'name' => 'Table 1', 'capacity' => 2, 'active' => true, 'is_available' => false],
            ['partner_location_id' => $locationId, 'name' => 'Table 3', 'capacity' => 6, 'active' => false, 'is_available' => true],
            ['partner_location_id' => $otherLocationId, 'name' => 'Table 1', 'capacity' => 2, 'active' => true, 'is_available' => true],
        ]);

        $response = $this->getJson("/api/merchant-locations/{$locationId}/available-tables");

        $response->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('location.id', $locationId)
            ->assertJsonCount(1, 'tables')
            ->assertJsonPath('tables.0.name', 'Table 2')
            ->assertJsonPath('tables.0.capacity', 4);
    }

    public function test_it_rejects_an_inactive_location(): void
    {
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'active' => false,
        ]);

        $this->getJson("/api/merchant-locations/{$locationId}/available-tables")
            ->assertNotFound()
            ->assertJsonPath('status', 0);
    }

    public function test_it_hides_tables_when_dine_in_is_disabled_for_the_location(): void
    {
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'active' => true,
        ]);

        DB::table('partner_location_tables')->insert([
            'partner_location_id' => $locationId,
            'name' => 'Table 1',
            'capacity' => 2,
            'active' => true,
            'is_available' => true,
        ]);

        DB::table('partner_location_checkout_options')->insert([
            ['partner_location_id' => $locationId, 'type' => 'delivery', 'active' => true],
            ['partner_location_id' => $locationId, 'type' => 'pickup', 'active' => true],
            ['partner_location_id' => $locationId, 'type' => 'dine_in', 'active' => false],
        ]);

        $this->getJson("/api/merchant-locations/{$locationId}/available-tables")
            ->assertOk()
            ->assertJsonCount(0, 'tables');
    }
}
