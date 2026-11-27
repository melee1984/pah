<?php

namespace Tests\Feature\Api;

use App\LibraryStatus;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\DiningFixtures;
use Tests\TestCase;

class MobileDeliveryPaymentTest extends TestCase
{
    use DiningFixtures, RefreshDatabase;

    private string $customerUrl;

    private string $riderUrl;

    private User $customer;

    private User $rider;

    private int $deliveryId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createDiningDomainSchema();
        $this->withHeader('X-Admin-Request', 'apiRequestHandle001');
        config([
            'payment.mobile_delivery_enabled' => true,
            'services.quickpay.merchant_keys.17' => 'qp_test_fixture',
        ]);

        $this->customer = $this->account('buyer');
        $this->rider = $this->account('rider');
        $riderId = $this->approveRider($this->rider);
        $orderId = DB::table('order')->insertGetId([
            'cart_id' => 1, 'user_id' => $this->customer->id, 'partner_id' => 17,
            'order_status_id' => LibraryStatus::STATUS_ARRIVAL_AT_CUSTOMER,
        ]);
        $reference = (string) Str::uuid();
        $this->deliveryId = DB::table('rider_api_deliveries')->insertGetId([
            'reference' => $reference, 'legacy_order_id' => $orderId, 'rider_id' => $riderId,
            'current_state' => 'arrived_at_customer', 'cod_centavos' => 12000,
        ]);
        DB::table('payment_method')->insert([
            'id' => 8, 'title' => 'QR Ph', 'quickpay_channel' => 'qrph', 'active' => true, 'available_in_qr' => true,
        ]);

        $this->customerUrl = "/api/v1/user/orders/{$orderId}/payment";
        $this->riderUrl = "/api/v1/rider/deliveries/{$reference}/payment";
    }

    public function test_customer_and_rider_share_a_single_verified_payment(): void
    {
        $gatewayStatus = 'open';
        $reference = null;
        $verifiedAmount = 12000;
        Http::preventStrayRequests();
        Http::fake(['https://quickpayph.com/api/v1/checkouts*' => function (Request $request) use (&$gatewayStatus, &$reference, &$verifiedAmount) {
            $reference ??= $request['reference'];

            return Http::response([
                'object' => 'checkout', 'id' => 'cs_delivery', 'reference' => $reference,
                'status' => $gatewayStatus, 'amount' => $verifiedAmount, 'currency' => 'PHP', 'livemode' => false,
                'url' => 'https://quickpayph.com/pay/cs_delivery', 'qr' => ['emv' => 'qr-code'],
            ]);
        }]);

        $this->actingAs($this->customer, 'api')->getJson($this->customerUrl)->assertOk()
            ->assertJsonPath('payment_methods.0.id', 8)->assertJsonPath('cod_centavos', 12000);
        $input = $this->paymentInput();
        $checkoutId = $this->postJson($this->customerUrl, $input)->assertAccepted()
            ->assertJsonPath('payment.checkout_url', 'https://quickpayph.com/pay/cs_delivery')->json('payment.id');
        $this->postJson($this->customerUrl, $input)->assertAccepted()->assertJsonPath('payment.id', $checkoutId);
        $this->actingAs($this->rider, 'sanctum')->getJson($this->riderUrl)->assertOk()
            ->assertJsonPath('payment.id', $checkoutId)->assertJsonPath('payment.qr_payload', 'qr-code');
        $this->postJson($this->riderUrl, $this->paymentInput())->assertConflict();
        $this->confirmCash()->assertConflict();
        $this->postJson(str_replace('/payment', '/events', $this->riderUrl), [
            'type' => 'cod_collected', 'event_id' => (string) Str::uuid(), 'occurred_at' => now()->toISOString(),
        ])->assertConflict();
        $this->postJson(str_replace('/payment', '/events', $this->riderUrl), [
            'type' => 'failed', 'event_id' => (string) Str::uuid(), 'occurred_at' => now()->toISOString(),
        ])->assertConflict();

        $gatewayStatus = 'paid';
        $verifiedAmount = 12100;
        $this->postJson($this->riderUrl.'/status')->assertStatus(502);
        $this->assertDatabaseHas('rider_api_deliveries', ['id' => $this->deliveryId, 'cod_centavos' => 12000]);
        $verifiedAmount = 12000;
        $this->artisan('quickpay:reconcile')->assertSuccessful();
        $this->getJson($this->riderUrl)->assertOk()
            ->assertJsonPath('payment.status', 'paid')->assertJsonPath('cod_centavos', 0)
            ->assertJsonPath('payment.qr_payload', null);
        $this->confirmCash()->assertConflict();
        $this->actingAs($this->customer, 'api')->getJson($this->customerUrl)->assertOk()
            ->assertJsonPath('payment.status', 'paid');

        Http::assertSentCount(4);
        $this->assertDatabaseCount('quickpay_checkouts', 1);
        $this->assertDatabaseCount('rider_api_wallets', 0);
    }

    public function test_expired_checkout_allows_cash_and_the_original_key_cannot_charge_again(): void
    {
        Http::preventStrayRequests();
        Http::fake(['https://quickpayph.com/api/v1/checkouts' => fn (Request $request) => Http::response([
            'object' => 'checkout', 'id' => 'cs_expired', 'reference' => $request['reference'],
            'status' => 'expired', 'amount' => 12000, 'currency' => 'PHP', 'livemode' => false,
        ])]);

        $input = $this->paymentInput();
        $this->actingAs($this->rider, 'sanctum')->postJson($this->riderUrl, $input)
            ->assertOk()->assertJsonPath('payment.status', 'expired')->assertJsonPath('cash_collection_blocked', false);
        $this->confirmCash()->assertOk();
        $this->confirmCash()->assertConflict();
        $this->postJson($this->riderUrl, $input)->assertOk()->assertJsonPath('payment.status', 'expired');
        $this->postJson($this->riderUrl, $this->paymentInput())->assertConflict();
        Http::assertSentCount(1);
        $this->assertDatabaseCount('rider_api_delivery_events', 1);
    }

    public function test_a_lost_create_can_be_recovered_with_the_original_gateway_key(): void
    {
        $gatewayKeys = [];
        Http::preventStrayRequests();
        Http::fake(['https://quickpayph.com/api/v1/checkouts' => function (Request $request) use (&$gatewayKeys) {
            $gatewayKeys[] = $request->header('Idempotency-Key')[0];

            return count($gatewayKeys) === 1 ? Http::response([], 503) : Http::response([
                'object' => 'checkout', 'id' => 'cs_recovered', 'reference' => $request['reference'],
                'status' => 'open', 'amount' => 12000, 'currency' => 'PHP', 'livemode' => false,
                'url' => 'https://quickpayph.com/pay/cs_recovered',
            ]);
        }]);

        $this->actingAs($this->customer, 'api')->postJson($this->customerUrl, $this->paymentInput())->assertServiceUnavailable();
        $this->actingAs($this->rider, 'sanctum')->postJson($this->riderUrl.'/status')->assertAccepted()
            ->assertJsonPath('payment.checkout_url', 'https://quickpayph.com/pay/cs_recovered');
        $this->assertSame($gatewayKeys[0], $gatewayKeys[1]);
        $this->assertDatabaseCount('quickpay_checkouts', 1);
    }

    public function test_ownership_eligibility_and_amount_are_checked_before_creating_checkout(): void
    {
        Http::fake();
        $other = $this->account('other');
        $this->approveRider($other);

        $this->actingAs($other, 'api')->getJson($this->customerUrl)->assertNotFound();
        $this->postJson($this->customerUrl, $this->paymentInput())->assertNotFound();
        $this->actingAs($other, 'sanctum')->getJson($this->riderUrl)->assertNotFound();
        $this->postJson($this->riderUrl, $this->paymentInput())->assertNotFound();

        $this->actingAs($this->customer, 'api')->postJson($this->customerUrl, [
            'expected_total_centavos' => 11000,
        ] + $this->paymentInput())->assertConflict();
        DB::table('rider_api_deliveries')->where('id', $this->deliveryId)->update(['cod_centavos' => 12010]);
        $this->getJson($this->customerUrl)->assertOk()->assertJsonPath('payment_methods', []);
        $this->postJson($this->customerUrl, $this->paymentInput())->assertConflict();

        config(['payment.mobile_delivery_enabled' => false]);
        $this->postJson($this->customerUrl, $this->paymentInput())->assertUnprocessable();
        Http::assertNothingSent();
    }

    private function account(string $name): User
    {
        return User::forceCreate(['name' => $name, 'email' => "{$name}@delivery.test", 'password' => bcrypt('password')]);
    }

    private function approveRider(User $user): int
    {
        return DB::table('rider')->insertGetId([
            'user_id' => $user->id, 'name' => $user->name, 'active' => true, 'date_join' => now(),
        ]);
    }

    private function paymentInput(): array
    {
        return ['payment_method_id' => 8, 'expected_total_centavos' => 12000, 'idempotency_key' => (string) Str::uuid()];
    }

    private function confirmCash(): TestResponse
    {
        return $this->postJson(str_replace('/payment', '/cod/confirm', $this->riderUrl), [
            'amount_centavos' => 12000, 'collected_at' => now()->subSecond()->toISOString(),
        ]);
    }
}
