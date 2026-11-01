<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\UserController;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
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
            $table->string('city_name');
            $table->decimal('lat', 10, 7);
            $table->decimal('long', 10, 7);
            $table->boolean('active')->default(true);
            $table->boolean('default')->default(false);
            $table->timestamps();
        });
    }

    public function test_adding_an_address_saves_an_active_city(): void
    {
        DB::table('cities')->insert([
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
        $this->assertTrue($response->getData(true)['serviceable']);
        $this->assertSame('Davao City', $response->getData(true)['address']['city']['name']);
        $this->assertDatabaseHas('user_address', [
            'user_id' => 10,
            'city_name' => 'Davao City',
            'address_1' => '123 Sample Street',
            'default' => true,
        ]);
    }

    public function test_adding_an_address_saves_a_city_that_is_not_active(): void
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

        $response = app(UserController::class)->addAddress($request);

        $this->assertSame(1, $response->getData(true)['status']);
        $this->assertFalse($response->getData(true)['serviceable']);
        $this->assertFalse($response->getData(true)['address']['city']['active']);
        $this->assertDatabaseHas('user_address', [
            'user_id' => 10,
            'city_name' => 'Inactive City',
            'address_1' => '123 Sample Street',
            'default' => false,
        ]);
    }

    public function test_updating_an_address_makes_it_active_and_the_only_default(): void
    {
        DB::table('cities')->insert([
            'name' => 'Davao City',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldDefaultId = $this->createAddress(10, 'Home', true, true);
        $newDefaultId = $this->createAddress(10, 'Office', false, false);

        $request = Request::create('/api/mobile/address/status/update/submit', 'POST', [
            'address_id' => $newDefaultId,
            'active' => true,
            'default' => true,
        ]);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 10]));

        $response = app(UserController::class)->updateAddressStatus($request);

        $this->assertSame(1, $response->getData(true)['status']);
        $this->assertDatabaseHas('user_address', [
            'id' => $newDefaultId,
            'active' => true,
            'default' => true,
        ]);
        $this->assertDatabaseHas('user_address', [
            'id' => $oldDefaultId,
            'default' => false,
        ]);
    }

    public function test_updating_an_address_cannot_change_another_users_address(): void
    {
        DB::table('cities')->insert([
            'name' => 'Davao City',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $addressId = $this->createAddress(20, 'Home', true, true);

        $request = Request::create('/api/mobile/address/status/update/submit', 'POST', [
            'address_id' => $addressId,
            'active' => false,
            'default' => false,
        ]);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 10]));

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        app(UserController::class)->updateAddressStatus($request);
    }

    public function test_an_inactive_address_cannot_be_the_default(): void
    {
        $request = Request::create('/api/mobile/address/status/update/submit', 'POST', [
            'address_id' => 1,
            'active' => false,
            'default' => true,
        ]);
        $request->setUserResolver(fn () => (new User)->forceFill(['id' => 10]));

        try {
            app(UserController::class)->updateAddressStatus($request);
            $this->fail('The invalid active/default combination was accepted.');
        } catch (ValidationException $exception) {
            $this->assertSame('A default address must be active.', $exception->errors()['default'][0]);
        }
    }

    private function createAddress(int $userId, string $title, bool $active, bool $default): int
    {
        return DB::table('user_address')->insertGetId([
            'user_id' => $userId,
            'title' => $title,
            'address_1' => $title.' address',
            'city_name' => 'Davao City',
            'lat' => 7.0731,
            'long' => 125.6128,
            'active' => $active,
            'default' => $default,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
