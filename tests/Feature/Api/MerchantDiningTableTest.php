<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MerchantDiningTableTest extends TestCase
{
    use RefreshDatabase;

    private User $merchantUser;

    private int $locationId;

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
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'Merchant',
            'email' => 'tables@example.com',
            'password' => bcrypt('password'),
        ]);
        $partnerId = DB::table('partners')->insertGetId(['user_id' => $userId]);
        $this->locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => $partnerId,
            'active' => true,
        ]);
        $this->merchantUser = User::query()->findOrFail($userId);
    }

    public function test_merchant_can_create_update_list_and_delete_a_dining_table(): void
    {
        $create = $this->actingAs($this->merchantUser)->postJson(
            "/api/merchant/location/{$this->locationId}/tables",
            ['name' => 'Table 1', 'capacity' => 4, 'active' => true, 'is_available' => true]
        );

        $create->assertCreated()
            ->assertJsonPath('table.name', 'Table 1')
            ->assertJsonPath('table.capacity', 4);

        $tableId = $create->json('table.id');

        $this->actingAs($this->merchantUser)->putJson(
            "/api/merchant/location/{$this->locationId}/tables/{$tableId}",
            ['name' => 'Window Table', 'capacity' => 6, 'active' => true, 'is_available' => false]
        )->assertOk()
            ->assertJsonPath('table.name', 'Window Table')
            ->assertJsonPath('table.is_available', false);

        $this->actingAs($this->merchantUser)
            ->getJson("/api/merchant/location/{$this->locationId}/tables")
            ->assertOk()
            ->assertJsonCount(1, 'tables');

        $this->actingAs($this->merchantUser)
            ->deleteJson("/api/merchant/location/{$this->locationId}/tables/{$tableId}")
            ->assertOk()
            ->assertJsonCount(0, 'tables');

        $this->assertDatabaseMissing('partner_location_tables', ['id' => $tableId]);
    }

    public function test_table_names_must_be_unique_within_a_branch(): void
    {
        DB::table('partner_location_tables')->insert([
            'partner_location_id' => $this->locationId,
            'name' => 'Table 1',
            'capacity' => 2,
            'active' => true,
            'is_available' => true,
        ]);

        $this->actingAs($this->merchantUser)->postJson(
            "/api/merchant/location/{$this->locationId}/tables",
            ['name' => 'Table 1', 'capacity' => 4, 'active' => true, 'is_available' => true]
        )->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }
}
