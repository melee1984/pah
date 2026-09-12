<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\Partners;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MerchantOrderLocationFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('partner_location_address_id');
            $table->unsignedBigInteger('option_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        DB::table('partners')->insert([
            ['id' => 20, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 99, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_orders_are_scoped_to_the_selected_merchant_location(): void
    {
        $locationOrderId = $this->createOrder(partnerId: 20, locationId: 34);
        $this->createOrder(partnerId: 20, locationId: 35);
        $this->createOrder(partnerId: 99, locationId: 34);

        $merchant = new Partners;
        $merchant->id = 20;

        $user = new User;
        $user->setRelation('merchant', $merchant);

        $request = Request::create('/api/merchant/orders', 'GET', [
            'merchant_location_id' => 34,
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->orders($request);
        $orders = $response->getData(true)['orders'];

        $this->assertCount(1, $orders);
        $this->assertSame($locationOrderId, $orders[0]['id']);
        $this->assertSame(34, $orders[0]['cart']['partner_location_address_id']);
    }

    private function createOrder(int $partnerId, int $locationId): int
    {
        $cartId = DB::table('cart')->insertGetId([
            'partner_id' => $partnerId,
            'partner_location_address_id' => $locationId,
            'option_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('order')->insertGetId([
            'user_id' => 10,
            'partner_id' => $partnerId,
            'cart_id' => $cartId,
            'status_id' => 1,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
