<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MerchantLocationCheckoutOptionsTest extends TestCase
{
    use RefreshDatabase;

    private User $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('city');
            $table->string('zip_code');
            $table->string('mobile');
            $table->string('telephone');
            $table->string('latitude');
            $table->string('longtitude');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'Merchant',
            'email' => 'branch-owner@example.com',
            'password' => bcrypt('password'),
        ]);
        DB::table('partners')->insert(['user_id' => $userId]);
        $this->merchantUser = User::query()->findOrFail($userId);
    }

    public function test_branch_requires_at_least_one_checkout_option(): void
    {
        $this->actingAs($this->merchantUser)
            ->postJson('/api/merchant/location/submit', $this->branchPayload([]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('checkout_options');
    }

    public function test_selected_checkout_options_are_saved_with_the_branch(): void
    {
        $this->actingAs($this->merchantUser)
            ->postJson('/api/merchant/location/submit', $this->branchPayload(['pickup']))
            ->assertOk()
            ->assertJsonPath('status', 1);

        $locationId = DB::table('partner_location')->value('id');

        $this->assertDatabaseHas('partner_location_checkout_options', [
            'partner_location_id' => $locationId,
            'type' => 'pickup',
            'active' => true,
        ]);
        $this->assertDatabaseHas('partner_location_checkout_options', [
            'partner_location_id' => $locationId,
            'type' => 'delivery',
            'active' => false,
        ]);

        $this->actingAs($this->merchantUser)
            ->getJson('/api/merchant/location/list')
            ->assertOk()
            ->assertJsonCount(3, 'location.data.0.checkout_options');
    }

    private function branchPayload(array $checkoutOptions): array
    {
        return [
            'address_1' => '123 Test Street',
            'address_2' => 'Downtown',
            'city' => 'Davao City',
            'zip' => '8000',
            'mobile' => '09171234567',
            'telephone' => '2212345',
            'latitude' => '7.0707',
            'longtitude' => '125.6087',
            'active' => true,
            'checkout_options' => $checkoutOptions,
        ];
    }
}
