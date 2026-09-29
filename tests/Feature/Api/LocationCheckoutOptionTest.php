<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LocationCheckoutOptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }

    public function test_an_unconfigured_location_defaults_to_all_checkout_options(): void
    {
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'active' => true,
        ]);

        $this->getJson("/api/merchant-locations/{$locationId}/checkout-options")
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonCount(3, 'options')
            ->assertJsonPath('options.0.type', 'delivery')
            ->assertJsonPath('options.1.type', 'pickup')
            ->assertJsonPath('options.2.type', 'dine_in');
    }

    public function test_it_only_returns_options_enabled_for_the_location(): void
    {
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'active' => true,
        ]);

        DB::table('partner_location_checkout_options')->insert([
            ['partner_location_id' => $locationId, 'type' => 'delivery', 'active' => true],
            ['partner_location_id' => $locationId, 'type' => 'pickup', 'active' => false],
            ['partner_location_id' => $locationId, 'type' => 'dine_in', 'active' => true],
        ]);

        $response = $this->getJson("/api/merchant-locations/{$locationId}/checkout-options");

        $response->assertOk()
            ->assertJsonCount(2, 'options')
            ->assertJsonPath('options.0.type', 'delivery')
            ->assertJsonPath('options.1.type', 'dine_in');
    }

    public function test_it_rejects_an_inactive_location(): void
    {
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => 10,
            'active' => false,
        ]);

        $this->getJson("/api/merchant-locations/{$locationId}/checkout-options")
            ->assertNotFound();
    }

    public function test_a_merchant_can_replace_the_enabled_options_for_its_location(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Merchant',
            'email' => 'merchant@example.com',
            'password' => bcrypt('password'),
        ]);
        $partnerId = DB::table('partners')->insertGetId(['user_id' => $userId]);
        $locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => $partnerId,
            'active' => true,
        ]);

        $response = $this->actingAs(User::query()->findOrFail($userId))->putJson(
            "/api/merchant/location/{$locationId}/checkout-options",
            ['options' => ['pickup']]
        );

        $response->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonCount(1, 'options')
            ->assertJsonPath('options.0.type', 'pickup');

        $this->assertDatabaseHas('partner_location_checkout_options', [
            'partner_location_id' => $locationId,
            'type' => 'delivery',
            'active' => false,
        ]);
        $this->assertDatabaseHas('partner_location_checkout_options', [
            'partner_location_id' => $locationId,
            'type' => 'pickup',
            'active' => true,
        ]);
    }
}
