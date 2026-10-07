<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Api\Admin\OrderController;
use App\LibraryStatus;
use App\Model\Orders\Orders;
use App\Services\RiderOfferDispatcher;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RiderOfferRetryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('store_accepted_at')->nullable();
            $table->unsignedBigInteger('order_status_id')->nullable();
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->unsignedBigInteger('accepted_by_rider_id')->nullable();
            $table->timestamps();
        });
        Schema::create('rider_api_deliveries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legacy_order_id');
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->string('current_state');
        });
        Schema::create('rider_api_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id');
            $table->string('status');
            $table->timestamp('expires_at');
        });
        Schema::create('rider', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
        });
    }

    public function test_retry_sends_a_new_offer_for_an_accepted_unassigned_order(): void
    {
        $order = $this->order();
        $deliveryId = DB::table('rider_api_deliveries')->insertGetId([
            'legacy_order_id' => $order->id,
            'current_state' => 'offered',
        ]);
        $dispatcher = new class($deliveryId) extends RiderOfferDispatcher
        {
            public function __construct(private int $deliveryId) {}

            public function dispatchOrder(Orders $order): ?string
            {
                DB::table('rider_api_offers')->insert([
                    'delivery_id' => $this->deliveryId,
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(15),
                ]);

                return 'delivery-reference';
            }
        };

        $response = (new OrderController)->retryRiderOffers($order, $dispatcher);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $response->getData(true)['new_offers']);
        $this->assertSame(1, $response->getData(true)['active_offers']);
    }

    public function test_retry_rejects_orders_that_are_not_accepted_or_already_assigned(): void
    {
        $order = $this->order();
        $dispatcher = new class extends RiderOfferDispatcher
        {
            public function dispatchOrder(Orders $order): ?string
            {
                throw new \RuntimeException('Dispatch should not run.');
            }
        };

        $order->store_accepted_at = null;
        $this->assertSame(409, (new OrderController)->retryRiderOffers($order, $dispatcher)->getStatusCode());

        $order->store_accepted_at = now();
        $order->rider_id = 5;
        $this->assertSame(409, (new OrderController)->retryRiderOffers($order, $dispatcher)->getStatusCode());
    }

    public function test_retry_counts_an_expired_offer_renewed_in_place_as_a_new_offer(): void
    {
        $order = $this->order();
        $deliveryId = DB::table('rider_api_deliveries')->insertGetId([
            'legacy_order_id' => $order->id,
            'current_state' => 'offered',
        ]);
        DB::table('rider_api_offers')->insert([
            'delivery_id' => $deliveryId,
            'status' => 'pending',
            'expires_at' => now()->subMinute(),
        ]);
        $dispatcher = new class($deliveryId) extends RiderOfferDispatcher
        {
            public function __construct(private int $deliveryId) {}

            public function dispatchOrder(Orders $order): ?string
            {
                DB::table('rider_api_offers')->where('delivery_id', $this->deliveryId)->update([
                    'status' => 'pending',
                    'expires_at' => now()->addMinutes(15),
                ]);

                return 'delivery-reference';
            }
        };

        $response = (new OrderController)->retryRiderOffers($order, $dispatcher);

        $this->assertSame(1, $response->getData(true)['new_offers']);
        $this->assertSame('Sent this order to 1 available rider(s).', $response->getData(true)['message']);
        $this->assertDatabaseCount('rider_api_offers', 1);
    }

    public function test_manual_selection_sends_a_targeted_offer_without_prematurely_assigning_the_order(): void
    {
        $order = $this->order();
        DB::table('rider')->insert(['id' => 5, 'active' => true]);
        $dispatcher = new class extends RiderOfferDispatcher
        {
            public function dispatchOrderToRider(Orders $order, int $riderId): ?string
            {
                return $riderId === 5 ? 'delivery-reference' : null;
            }
        };

        $response = (new OrderController)->updateOrderRider(
            new Request(['rider_id' => 5]),
            $order,
            $dispatcher,
        );

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Offer sent to the selected rider.', $response->getData(true)['message']);
        $this->assertNull($order->fresh()->rider_id);
    }

    private function order(): Orders
    {
        $order = new Orders;
        $order->submitted_at = now();
        $order->store_accepted_at = now();
        $order->order_status_id = LibraryStatus::STATUS_PROCESSING;
        $order->save();

        return $order;
    }
}
