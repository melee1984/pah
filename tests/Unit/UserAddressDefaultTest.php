<?php

namespace Tests\Unit;

use App\Model\User\UserAddress;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserAddressDefaultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->string('address_1');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('default')->default(false);
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_an_address_exposes_its_city(): void
    {
        $cityId = \DB::table('cities')->insertGetId([
            'name' => 'Davao City',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $address = UserAddress::query()->create(
            $this->addressAttributes('Home', false) + ['city_id' => $cityId]
        );

        $this->assertSame('Davao City', $address->city->name);
    }

    public function test_the_latest_added_address_becomes_the_only_default(): void
    {
        UserAddress::query()->create($this->addressAttributes('Home', true));
        UserAddress::query()->create($this->addressAttributes('Office', true));
        $latest = UserAddress::query()->create($this->addressAttributes('Parents', false));

        $latest->makeDefault();

        $this->assertSame(1, UserAddress::query()->where('user_id', 10)->where('default', true)->count());
        $this->assertTrue($latest->fresh()->default);
        $this->assertFalse(UserAddress::query()->where('title', 'Home')->firstOrFail()->default);
        $this->assertFalse(UserAddress::query()->where('title', 'Office')->firstOrFail()->default);
    }

    private function addressAttributes(string $title, bool $default): array
    {
        return [
            'user_id' => 10,
            'title' => $title,
            'address_1' => $title.' address',
            'active' => true,
            'default' => $default,
        ];
    }
}
