<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\LibraryStatus;
use App\Model\Bookings\BookingStatus;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\Partners;
use App\Services\AgentCommissionService;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
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
        Queue::fake();
        config([
            'checkout.convenience_fee_rate' => 0.05,
            'checkout.vat_rate' => 12,
        ]);
        $this->mock(AgentCommissionService::class, function ($mock) {
            $mock->shouldReceive('sync')->andReturnNull();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('device_token_food')->nullable();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name')->nullable();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('partner_location_address_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('order_no')->nullable();
            $table->string('fulfillment_type')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->unsignedInteger('qty');
            $table->decimal('price', 8, 2);
            $table->decimal('variance_total', 8, 2)->default(0);
            $table->decimal('price_comm_total', 8, 2)->default(0);
            $table->decimal('variance_total_comm_total', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->string('instruction')->nullable();
            $table->timestamps();
        });

        Schema::create('payment_method', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('library_status', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->string('description')->nullable();
            $table->unsignedInteger('sorting')->nullable();
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
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('order_status_id');
            $table->unsignedBigInteger('booking_status_id')->nullable();
            $table->unsignedBigInteger('accepted_by_store_id')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('store_accepted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });

        Schema::create('order_process', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 10]);
        DB::table('partners')->insert(['id' => 20, 'restaurant_name' => 'Test Merchant']);
        DB::table('products')->insert(['id' => 30, 'title' => 'Chicken Inasal']);
        DB::table('payment_method')->insert(['id' => 3, 'title' => 'Cash on Delivery']);

        foreach ([
            LibraryStatus::STATUS_ORDER_PLACED => 'Order Placed',
            LibraryStatus::STATUS_ORDER_ACCEPTED => 'Order Accepted',
            LibraryStatus::STATUS_PROCESSING => 'Processing',
            LibraryStatus::STATUS_READY_FOR_PICKUP => 'Ready for Pickup',
            LibraryStatus::STATUS_DELIVERED => 'Delivered',
            LibraryStatus::STATUS_CANCELLED => 'Cancelled',
            LibraryStatus::STATUS_COMPLETED => 'Completed',
        ] as $id => $title) {
            DB::table('library_status')->insert(compact('id', 'title'));
        }

        DB::table('library_booking_status')->insert([
            'id' => BookingStatus::STATUS_BOOKING_PLACED,
            'title' => 'Booking Placed',
        ]);
    }

    public function test_pickup_order_moves_to_processing_without_a_delivery_status(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_PICKUP);

        $response = (new OrderController)->acceptOrder($order, $this->merchantRequest());

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_PROCESSING,
            'booking_status_id' => null,
            'accepted_by_store_id' => 200,
        ]);
        $this->assertSame('Ready For Pickup', $response->getData(true)['action']['button']['label']);
        $this->assertFalse($response->getData(true)['action']['send_to_rider']);

        $printJob = $response->getData(true)['print_job'];
        $this->assertSame('order-'.$order->id.'-accept', $printJob['id']);
        $this->assertSame('plain_text', $printJob['format']);
        $this->assertSame(1, $printJob['copies']);
        $this->assertSame(3, $printJob['feed_lines']);
        $this->assertStringContainsString('Test Merchant', $printJob['content']);
        $this->assertStringContainsString('ORDER #TEST-100', $printJob['content']);
        $this->assertStringContainsString('2x Chicken Inasal', $printJob['content']);
        $this->assertStringContainsString('PHP 200.00', $printJob['content']);
        $this->assertStringContainsString('Convenience fee', $printJob['content']);
        $this->assertStringContainsString('PHP 10.00', $printJob['content']);
        $this->assertStringContainsString('VAT', $printJob['content']);
        $this->assertStringContainsString('PHP 1.20', $printJob['content']);
        $this->assertStringContainsString('Discount', $printJob['content']);
        $this->assertStringContainsString('-PHP 15.00', $printJob['content']);
        $this->assertLessThan(
            strpos($printJob['content'], 'VAT'),
            strpos($printJob['content'], 'Convenience fee'),
        );
        $this->assertLessThan(
            strpos($printJob['content'], 'Discount'),
            strpos($printJob['content'], 'VAT'),
        );
        $this->assertStringContainsString('Payment: Cash on Delivery', $printJob['content']);
        $this->assertStringContainsString('TOTAL', $printJob['content']);
    }

    public function test_pickup_order_moves_to_ready_for_pickup_before_completion(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_PICKUP);
        $controller = new OrderController;
        $request = $this->merchantRequest();
        $controller->acceptOrder($order, $request);

        $response = $controller->markOrderReadyForPickup($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order is ready for pickup.', $response->getData(true)['message']);
        $this->assertSame('Ready for Customer Pickup', $response->getData(true)['action']['label']);
        $this->assertSame('Complete Order', $response->getData(true)['action']['button']['label']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_READY_FOR_PICKUP,
            'booking_status_id' => null,
        ]);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $order->id,
            'status_id' => LibraryStatus::STATUS_READY_FOR_PICKUP,
            'user_id' => 10,
        ]);
    }

    public function test_merchant_completes_pickup_after_customer_collects_it(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_PICKUP);
        $controller = new OrderController;
        $request = $this->merchantRequest();
        $controller->acceptOrder($order, $request);
        $controller->markOrderReadyForPickup($order, $request);

        $response = $controller->acceptOrder(
            $order,
            $this->merchantRequest(action: 'complete'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order completed successfully.', $response->getData(true)['message']);
        $this->assertSame('Order Completed', $response->getData(true)['action']['label']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_COMPLETED,
            'booking_status_id' => null,
        ]);
        $this->assertNotNull(Orders::find($order->id)->delivered_at);
    }

    public function test_dine_in_order_becomes_completed_when_ready_to_serve(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_DINE_IN);
        $controller = new OrderController;
        $controller->acceptOrder($order, $this->merchantRequest());

        $response = $controller->acceptOrder(
            $order,
            $this->merchantRequest(action: 'ready-for-pickup'),
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_COMPLETED,
            'booking_status_id' => null,
        ]);
        $this->assertNotNull(Orders::find($order->id)->delivered_at);
    }

    public function test_delivery_order_keeps_the_ready_for_pickup_delivery_flow(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_DELIVERY);
        $controller = new OrderController;
        $request = $this->merchantRequest();
        $controller->acceptOrder($order, $request);

        $response = $controller->markOrderReadyForPickup($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order is ready for pickup.', $response->getData(true)['message']);
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_READY_FOR_PICKUP,
            'booking_status_id' => BookingStatus::STATUS_BOOKING_PLACED,
        ]);
    }

    public function test_delivery_order_cannot_use_pickup_completion(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_DELIVERY);
        $controller = new OrderController;
        $request = $this->merchantRequest();
        $controller->acceptOrder($order, $request);
        $controller->markOrderReadyForPickup($order, $request);

        $response = $controller->completeOrder($order, $request);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertSame(
            'Only pickup orders can be completed by the merchant.',
            $response->getData(true)['message'],
        );
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_READY_FOR_PICKUP,
        ]);
    }

    public function test_completing_a_pickup_order_is_idempotent(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_PICKUP);
        $controller = new OrderController;
        $request = $this->merchantRequest();
        $controller->acceptOrder($order, $request);
        $controller->markOrderReadyForPickup($order, $request);
        $controller->completeOrder($order, $request);

        $response = $controller->completeOrder($order, $request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Order was already completed.', $response->getData(true)['message']);
        $this->assertDatabaseCount('order_process', 4);
    }

    public function test_pickup_order_cannot_be_completed_before_it_is_ready(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_PICKUP);
        $controller = new OrderController;
        $request = $this->merchantRequest();
        $controller->acceptOrder($order, $request);

        $response = $controller->completeOrder($order, $request);

        $this->assertSame(409, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_PROCESSING,
        ]);
    }

    public function test_pending_order_cannot_skip_acceptance(): void
    {
        $order = $this->createOrder(Cart::FULFILLMENT_PICKUP);

        $response = (new OrderController)->markOrderReadyForPickup($order, $this->merchantRequest());

        $this->assertSame(409, $response->getStatusCode());
        $this->assertDatabaseHas('order', [
            'id' => $order->id,
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
    }

    private function createOrder(string $fulfillmentType): Orders
    {
        $cartId = DB::table('cart')->insertGetId([
            'partner_id' => 20,
            'partner_location_address_id' => 200,
            'payment_id' => 3,
            'order_no' => 'TEST-100',
            'fulfillment_type' => $fulfillmentType,
            'discount_amount' => 15,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cart_details')->insert([
            'cart_id' => $cartId,
            'item_id' => 30,
            'qty' => 2,
            'price' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $orderId = DB::table('order')->insertGetId([
            'user_id' => 10,
            'cart_id' => $cartId,
            'partner_id' => 20,
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Orders::findOrFail($orderId);
    }

    private function merchantRequest(?string $action = null): Request
    {
        $merchant = new Partners;
        $merchant->id = 20;

        $user = User::findOrFail(10);
        $user->setRelation('merchant', $merchant);

        $parameters = ['store_location_id' => 200];
        if ($action) {
            $parameters['action'] = $action;
        }

        $request = Request::create('/api/merchant/orders/1/accept', 'POST', $parameters);
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
