<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\OrderController;
use App\LibraryStatus;
use App\Model\Bookings\BookingStatus;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\Services\AgentCommissionService;
use App\Services\RiderOfferDispatcher;
use App\Services\UserRewardService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerOrderCancellationTest extends TestCase
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

        $this->mock(AgentCommissionService::class, function ($mock) {
            $mock->shouldReceive('sync')->andReturnNull();
        });
        $this->mock(UserRewardService::class, function ($mock) {
            $mock->shouldReceive('sync')->andReturnNull();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('fulfillment_type')->nullable();
            $table->timestamps();
        });
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('order_status_id');
            $table->unsignedBigInteger('booking_status_id')->nullable();
            $table->timestamps();
        });
        Schema::create('order_process', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 10],
            ['id' => 11],
        ]);
        DB::table('cart')->insert([
            'id' => 20,
            'fulfillment_type' => Cart::FULFILLMENT_DELIVERY,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_customer_cancellation_updates_order_history_and_delivery(): void
    {
        $order = $this->createOrder();
        Log::spy();
        $this->mock(RiderOfferDispatcher::class, function ($mock) use ($order) {
            $mock->shouldReceive('cancelOrder')->once()->with($order->id);
        });

        $response = (new OrderController)->updateOrderStatus(
            $order,
            $this->requestForUser(10, ['status' => 'cancel', 'reason' => 'Changed my mind']),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_CANCELLED,
            'booking_status_id' => BookingStatus::STATUS_BOOKING_CANCELLED,
        ]);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => LibraryStatus::STATUS_CANCELLED,
            'user_id' => 10,
        ]);
        Log::shouldHaveReceived('info')->once()->with('Customer cancelled an order.', \Mockery::on(
            fn (array $context) => $context['order_id'] === $order->id
                && $context['reason'] === 'Changed my mind'
                && $context['already_cancelled'] === false
        ));
    }

    public function test_customer_cannot_cancel_another_users_order(): void
    {
        $order = $this->createOrder();
        $this->mock(RiderOfferDispatcher::class, function ($mock) {
            $mock->shouldNotReceive('cancelOrder');
        });

        $response = (new OrderController)->updateOrderStatus(
            $order,
            $this->requestForUser(11, ['status' => 'cancel']),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        $this->assertDatabaseCount('order_process', 0);
    }

    public function test_completed_order_cannot_be_cancelled(): void
    {
        $order = $this->createOrder(LibraryStatus::STATUS_COMPLETED);
        $this->mock(RiderOfferDispatcher::class, function ($mock) {
            $mock->shouldNotReceive('cancelOrder');
        });

        $response = (new OrderController)->updateOrderStatus(
            $order,
            $this->requestForUser(10, ['status' => 'cancel']),
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_COMPLETED,
        ]);
        $this->assertDatabaseCount('order_process', 0);
    }

    private function createOrder(int $status = LibraryStatus::STATUS_ORDER_PLACED): Orders
    {
        return Orders::query()->create([
            'user_id' => 10,
            'cart_id' => 20,
            'order_status_id' => $status,
        ]);
    }

    private function requestForUser(int $userId, array $data): Request
    {
        $request = Request::create('/api/mobile/cancel/order/1/submit', 'POST', $data);
        $request->setUserResolver(fn () => User::query()->findOrFail($userId));

        return $request;
    }
}
