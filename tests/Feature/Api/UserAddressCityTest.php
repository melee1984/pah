<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\UserController;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAddressCityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('address_1');
            $table->unsignedBigInteger('city_id');
            $table->decimal('lat', 10, 7);
            $table->decimal('long', 10, 7);
            $table->boolean('active')->default(true);
            $table->boolean('default')->default(false);
            $table->timestamps();
        });
    }

    public function test_adding_an_address_requires_and_saves_an_active_city(): void
    {
        $cityId = DB::table('cities')->insertGetId([
            'name' => 'Davao City',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/mobile/address/submit', 'POST', [
            'title' => 'Home',
            'address' => '123 Sample Street',
            'city' => 'Davao City',
            'latitude' => 7.0731,
            'longitude' => 125.6128,
        ]);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 10]));

        $response = app(UserController::class)->addAddress($request);

        $this->assertSame(1, $response->getData(true)['status']);
        $this->assertSame('Davao City', $response->getData(true)['address']['city']['name']);
        $this->assertDatabaseHas('user_address', [
            'user_id' => 10,
            'city_id' => $cityId,
            'address_1' => '123 Sample Street',
        ]);
    }

    public function test_adding_an_address_rejects_a_city_that_is_not_active(): void
    {
        DB::table('cities')->insert([
            'name' => 'Inactive City',
            'active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/mobile/address/submit', 'POST', [
            'title' => 'Home',
            'address' => '123 Sample Street',
            'city' => 'Inactive City',
            'latitude' => 7.0731,
            'longitude' => 125.6128,
        ]);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 10]));

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        app(UserController::class)->addAddress($request);
    }
}
