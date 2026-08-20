<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\Model\Orders\Orders;
use App\Partners;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MerchantOrderAcceptanceTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('device_token_food')->nullable();
        });

        Schema::create('library_booking_status', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('sorting')->nullable();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('accepted_by_store_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('store_accepted_at')->nullable();
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
            'id' => 10,
            'device_token_food' => null,
        ]);
        // 
        DB::table('library_booking_status')->insert([
            ['id' => Orders::STATUS_ORDER_PLACED, 'description' => 'Order Placed'],
            ['id' => Orders::STATUS_ORDER_ACCEPTED, 'description' => 'Order Accepted'],
            ['id' => Orders::STATUS_PROCESSING, 'description' => 'Processing'],
            ['id' => Orders::STATUS_READY_FOR_PICKUP, 'description' => 'Ready for Pickup'],
            ['id' => Orders::STATUS_RIDER_PICKED_UP, 'description' => 'Rider Picked Up'],
            ['id' => Orders::STATUS_DELIVERED, 'description' => 'Delivered'],
        ]);
    }

    public function test_authenticated_merchant_can_accept_its_pending_order(): void
    {
        $order = $this->createOrder(partnerId: 20);

        $response = (new OrderController)->acceptOrder(
            $order,
            $this->merchantRequest(merchantId: 20),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order accepted successfully.', $response->getData(true)['message']);
        $this->assertSame([
            'label' => 'Order Processing',
            'button' => [
                'label' => 'Ready For Pickup',
                'action' => 'ready-for-pickup',
            ],
            'cancel' => [
                'label' => 'Cancel Order',
                'action' => 'cancel',
            ],
            'send_to_rider' => true,
        ], $response->getData(true)['action']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_PROCESSING,
            'accepted_by_store_id' => 20,
        ]);
        $this->assertDatabaseCount('order_process', 2);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => Orders::STATUS_ORDER_ACCEPTED,
            'user_id' => 10,
        ]);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => Orders::STATUS_PROCESSING,
            'user_id' => 10,
        ]);
        $this->assertSame('Processing', Orders::findOrFail($order->id)->status->description);
    }

    public function test_merchant_cannot_accept_another_merchants_order(): void
    {
        $order = $this->createOrder(partnerId: 99);

        $response = (new OrderController)->acceptOrder(
            $order,
            $this->merchantRequest(merchantId: 20),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Order not found.', $response->getData(true)['message']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_ORDER_PLACED,
            'accepted_by_store_id' => null,
        ]);
        $this->assertDatabaseCount('order_process', 0);
    }

    public function test_accepting_the_same_order_again_is_idempotent(): void
    {
        $order = $this->createOrder(partnerId: 20);
        $request = $this->merchantRequest(merchantId: 20);
        $controller = new OrderController;

        $controller->acceptOrder($order, $request);
        $response = $controller->acceptOrder($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order was already accepted.', $response->getData(true)['message']);
        $this->assertDatabaseCount('order_process', 2);
    }

    public function test_merchant_can_mark_a_processing_order_ready_for_pickup(): void
    {
        $order = $this->createOrder(partnerId: 20);
        $request = $this->merchantRequest(merchantId: 20);
        $controller = new OrderController;
        $controller->acceptOrder($order, $request);

        $response = $controller->markOrderReadyForPickup($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order is ready for pickup.', $response->getData(true)['message']);
        $this->assertSame([
            'label' => 'Waiting for Rider to Pickup',
            'button' => null,
            'cancel' => [
                'label' => 'Cancel Order',
                'action' => 'cancel',
            ],
            'send_to_rider' => false,
        ], $response->getData(true)['action']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_READY_FOR_PICKUP,
        ]);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => Orders::STATUS_READY_FOR_PICKUP,
            'user_id' => 10,
        ]);
    }

    public function test_accept_endpoint_dispatches_ready_for_pickup_action(): void
    {
        $order = $this->createOrder(partnerId: 20);
        $request = $this->merchantRequest(merchantId: 20);
        $controller = new OrderController;
        $controller->acceptOrder($order, $request);

        $readyRequest = $this->merchantRequest(merchantId: 20, action: 'ready-for-pickup');
        $response = $controller->acceptOrder($order, $readyRequest);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order is ready for pickup.', $response->getData(true)['message']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_READY_FOR_PICKUP,
        ]);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => Orders::STATUS_READY_FOR_PICKUP,
            'user_id' => 10,
        ]);
    }

    public function test_pending_order_cannot_skip_processing(): void
    {
        $order = $this->createOrder(partnerId: 20);

        $response = (new OrderController)->markOrderReadyForPickup(
            $order,
            $this->merchantRequest(merchantId: 20),
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            'Only processing orders can be marked ready for pickup.',
            $response->getData(true)['message'],
        );
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_ORDER_PLACED,
        ]);
        $this->assertDatabaseCount('order_process', 0);
    }

    public function test_marking_an_order_ready_for_pickup_again_is_idempotent(): void
    {
        $order = $this->createOrder(partnerId: 20);
        $request = $this->merchantRequest(merchantId: 20);
        $controller = new OrderController;
        $controller->acceptOrder($order, $request);
        $controller->markOrderReadyForPickup($order, $request);

        $response = $controller->markOrderReadyForPickup($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order was already ready for pickup.', $response->getData(true)['message']);
        $this->assertDatabaseCount('order_process', 3);
    }

    public function test_merchant_can_cancel_its_order_through_the_accept_endpoint(): void
    {
        $order = $this->createOrder(partnerId: 20);
        $request = $this->merchantRequest(merchantId: 20, action: 'cancel');

        $response = (new OrderController)->acceptOrder($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order cancelled successfully.', $response->getData(true)['message']);
        $this->assertSame([
            'label' => 'Cancel Order',
            'button' => null,
            'cancel' => null,
            'send_to_rider' => false,
        ], $response->getData(true)['action']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_CANCELLED,
        ]);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => Orders::STATUS_CANCELLED,
            'user_id' => 10,
        ]);
    }

    public function test_merchant_cannot_cancel_another_merchants_order(): void
    {
        $order = $this->createOrder(partnerId: 99);

        $response = (new OrderController)->cancelOrder(
            $order,
            $this->merchantRequest(merchantId: 20),
        );

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Order not found.', $response->getData(true)['message']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_ORDER_PLACED,
        ]);
    }

    public function test_merchant_cannot_cancel_an_order_after_rider_pickup(): void
    {
        $order = $this->createOrder(partnerId: 20);
        $order->status_id = Orders::STATUS_RIDER_PICKED_UP;
        $order->save();

        $response = (new OrderController)->cancelOrder(
            $order,
            $this->merchantRequest(merchantId: 20),
        );

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            'Orders already picked up or completed cannot be cancelled.',
            $response->getData(true)['message'],
        );
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'status_id' => Orders::STATUS_RIDER_PICKED_UP,
        ]);
    }

    public function test_order_action_describes_the_merchant_display_for_each_status(): void
    {
        $order = $this->createOrder(partnerId: 20);

        $this->assertSame([
            'label' => 'Pending',
            'button' => [
                'label' => 'Accept Order',
                'action' => 'accept',
            ],
            'cancel' => [
                'label' => 'Cancel Order',
                'action' => 'cancel',
            ],
            'send_to_rider' => false,
        ], $order->getAction());

        $order->status_id = Orders::STATUS_PROCESSING;
        $this->assertSame([
            'label' => 'Order Processing',
            'button' => [
                'label' => 'Ready For Pickup',
                'action' => 'ready-for-pickup',
            ],
            'cancel' => [
                'label' => 'Cancel Order',
                'action' => 'cancel',
            ],
            'send_to_rider' => true,
        ], $order->getAction());

        $order->status_id = Orders::STATUS_READY_FOR_PICKUP;
        $this->assertSame([
            'label' => 'Waiting for Rider to Pickup',
            'button' => null,
            'cancel' => [
                'label' => 'Cancel Order',
                'action' => 'cancel',
            ],
            'send_to_rider' => false,
        ], $order->getAction());

        $order->status_id = Orders::STATUS_CANCELLED;
        $this->assertSame([
            'label' => 'Cancel Order',
            'button' => null,
            'cancel' => null,
            'send_to_rider' => false,
        ], $order->getAction());
    }

    private function createOrder(int $partnerId): Orders
    {
        $orderId = DB::table('order')->insertGetId([
            'user_id' => 10,
            'partner_id' => $partnerId,
            'status_id' => Orders::STATUS_ORDER_PLACED,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Orders::findOrFail($orderId);
    }

    private function merchantRequest(int $merchantId, ?string $action = null): Request
    {
        $merchant = new Partners;
        $merchant->id = $merchantId;

        $user = User::findOrFail(10);
        $user->setRelation('merchant', $merchant);

        $request = Request::create(
            '/api/merchant/orders/1/accept',
            'POST',
            $action ? ['action' => $action] : [],
        );
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
