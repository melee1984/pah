<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\Model\Orders\Orders;
use App\Partners;
use App\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class MerchantRiderOffersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->timestamp('store_accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_merchant_can_view_offer_candidates_and_the_assigned_rider(): void
    {
        $orderId = DB::table('order')->insertGetId([
            'partner_id' => 20,
            'store_accepted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $order = Orders::findOrFail($orderId);
        $request = $this->merchantRequest(20);

        $this->assertSame('not_dispatched', (new OrderController)->riderOffers($order, $request)->getData(true)['dispatch_status']);

        $deliveryReference = (string) Str::uuid();
        $deliveryId = DB::table('rider_api_deliveries')->insertGetId([
            'reference' => $deliveryReference,
            'legacy_order_id' => $orderId,
            'current_state' => 'offered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $riderId = DB::table('rider')->insertGetId([
            'name' => 'Nearby Rider',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rider_api_availability')->insert([
            'rider_id' => $riderId,
            'state' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $offerReference = (string) Str::uuid();
        DB::table('rider_api_offers')->insert([
            'reference' => $offerReference,
            'rider_id' => $riderId,
            'delivery_id' => $deliveryId,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = (new OrderController)->riderOffers($order, $request);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('offered', $response->getData(true)['dispatch_status']);
        $this->assertSame($offerReference, $response->getData(true)['offers'][0]['offer_id']);
        $this->assertSame($riderId, $response->getData(true)['offers'][0]['rider_id']);
        $this->assertSame('available', $response->getData(true)['offers'][0]['availability']);

        DB::table('rider_api_deliveries')->where('id', $deliveryId)->update([
            'rider_id' => $riderId,
            'current_state' => 'accepted',
        ]);
        DB::table('rider_api_offers')->where('delivery_id', $deliveryId)->update(['status' => 'accepted']);
        $assigned = (new OrderController)->riderOffers($order, $request)->getData(true);
        $this->assertSame('assigned', $assigned['dispatch_status']);
        $this->assertSame($riderId, $assigned['assigned_rider']['id']);
    }

    public function test_merchant_cannot_view_another_merchants_rider_offers(): void
    {
        $orderId = DB::table('order')->insertGetId([
            'partner_id' => 99,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ModelNotFoundException::class);
        (new OrderController)->riderOffers(Orders::findOrFail($orderId), $this->merchantRequest(20));
    }

    private function merchantRequest(int $merchantId): Request
    {
        $merchant = new Partners;
        $merchant->id = $merchantId;
        $user = new User;
        $user->setRelation('merchant', $merchant);
        $request = Request::create('/api/merchant/orders/1/rider-offers', 'GET');
        $request->setUserResolver(fn () => $user);

        return $request;
    }
}
