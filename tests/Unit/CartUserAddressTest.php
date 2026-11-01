<?php

namespace Tests\Unit;

use App\Model\CartUserAddress;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CartUserAddressTest extends TestCase
{
    public function test_it_captures_the_city_name_in_the_order_address_snapshot(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Schema::create('cart_user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('user_id');
            $table->string('city_name')->nullable();
            $table->timestamps();
        });

        $snapshot = CartUserAddress::query()->create([
            'cart_id' => 15,
            'user_id' => 9,
            'city_name' => 'Davao City',
        ]);

        $this->assertSame('Davao City', $snapshot->fresh()->city_name);
    }
}
