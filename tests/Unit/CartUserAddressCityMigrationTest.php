<?php

namespace Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartUserAddressCityMigrationTest extends TestCase
{
    public function test_it_backfills_city_names_from_city_ids_and_selected_addresses(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });
        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->string('city_name')->nullable();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('address_id')->nullable();
        });
        Schema::create('cart_user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('city_id')->nullable();
        });

        DB::table('cities')->insert(['id' => 5, 'name' => 'Davao City']);
        DB::table('user_address')->insert(['id' => 8, 'city_name' => 'Cebu City']);
        DB::table('cart')->insert([
            ['id' => 10, 'address_id' => null],
            ['id' => 11, 'address_id' => 8],
        ]);
        DB::table('cart_user_address')->insert([
            ['cart_id' => 10, 'city_id' => 5],
            ['cart_id' => 11, 'city_id' => null],
        ]);

        (require database_path('migrations/2026_11_01_000003_add_city_name_to_cart_user_address_table.php'))->up();

        $this->assertDatabaseHas('cart_user_address', [
            'cart_id' => 10,
            'city_name' => 'Davao City',
        ]);
        $this->assertDatabaseHas('cart_user_address', [
            'cart_id' => 11,
            'city_name' => 'Cebu City',
        ]);
    }
}
