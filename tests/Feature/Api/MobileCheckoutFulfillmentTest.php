<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\CheckoutController;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MobileCheckoutFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private int $locationId;

    private int $partnerId;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_type_id')->default(1);
            $table->boolean('no_delivery_fee')->default(false);
            $table->timestamps();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('address_1')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longtitude', 10, 7)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('partner_location_address_id');
            $table->unsignedBigInteger('address_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('fulfillment_type')->nullable();
            $table->unsignedBigInteger('dining_table_id')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('distance_rate', 8, 2)->default(0);
            $table->string('duration')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->decimal('user_lat', 10, 7)->nullable();
            $table->decimal('user_long', 10, 7)->nullable();
            $table->dateTime('sms_code_validated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedInteger('qty');
            $table->decimal('price', 8, 2);
            $table->decimal('variance_total', 8, 2)->default(0);
            $table->decimal('price_comm_total', 8, 2)->default(0);
            $table->decimal('variance_total_comm_total', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('payment_method', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('active')->default(true);
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('long', 10, 7)->nullable();
            $table->timestamps();
        });

        $this->partnerId = DB::table('partners')->insertGetId([
            'account_type_id' => 1,
            'no_delivery_fee' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->locationId = DB::table('partner_location')->insertGetId([
            'partner_id' => $this->partnerId,
            'address_1' => 'Merchant location',
            'latitude' => 10.3157000,
            'longtitude' => 123.8854000,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_method')->insert([
            'id' => 1,
            'title' => 'Credit card',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->user = new User;
        $this->user->id = 99;
        $this->user->firstname = 'Mobile';
        $this->user->lastname = 'Customer';
        $this->user->mobile = '09170000000';
        $this->user->email = 'mobile@example.com';
    }

    public function test_pickup_option_does_not_require_a_delivery_address(): void
    {
        $optionId = $this->createCheckoutOption('pickup');

        $response = $this->submitCheckout('pickup-session', $optionId);

        $this->assertSame(0, $response['status']);
        $this->assertSame(
            'We have encounter some issue when verifying the OPT code. Please try to re submit again.',
            $response['message']
        );
    }

    public function test_dine_in_option_does_not_require_a_delivery_address(): void
    {
        $optionId = $this->createCheckoutOption('dine_in');
        $tableId = DB::table('partner_location_tables')->insertGetId([
            'partner_location_id' => $this->locationId,
            'name' => 'Table 1',
            'capacity' => 4,
            'active' => true,
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->submitCheckout('dine-in-session', $optionId, [
            'dining_table_id' => $tableId,
        ]);

        $this->assertSame(0, $response['status']);
        $this->assertSame(
            'We have encounter some issue when verifying the OPT code. Please try to re submit again.',
            $response['message']
        );
    }

    public function test_delivery_option_still_requires_a_valid_delivery_address(): void
    {
        $optionId = $this->createCheckoutOption('delivery');

        $response = $this->submitCheckout('delivery-session', $optionId);

        $this->assertSame(0, $response['status']);
        $this->assertSame('The selected delivery address is invalid.', $response['message']);
    }

    public function test_updating_to_pickup_removes_delivery_charges_and_recomputes_summary(): void
    {
        $optionId = $this->createCheckoutOption('pickup');
        $cartId = $this->createCart('pickup-update-session', 50);
        $this->createCartItem($cartId);

        $response = $this->updateOrderOption('pickup-update-session', $optionId);

        $this->assertSame(1, $response['status']);
        $this->assertSame('pickup', $response['cart']['fulfillment_type']);
        $this->assertSame('0.00', $response['summary']['delivery_fee']);
        $this->assertSame('105.00', $response['summary']['total']);
        $this->assertDatabaseHas('cart', ['id' => $cartId, 'delivery_fee' => 0]);
    }

    public function test_updating_to_dine_in_removes_delivery_charges_and_recomputes_summary(): void
    {
        $optionId = $this->createCheckoutOption('dine_in');
        $cartId = $this->createCart('dine-in-update-session', 50);
        $this->createCartItem($cartId);

        $response = $this->updateOrderOption('dine-in-update-session', $optionId);

        $this->assertSame(1, $response['status']);
        $this->assertSame('dine_in', $response['cart']['fulfillment_type']);
        $this->assertNull($response['cart']['dining_table_id']);
        $this->assertSame('0.00', $response['summary']['delivery_fee']);
        $this->assertSame('105.00', $response['summary']['total']);
    }

    public function test_updating_to_delivery_requires_an_address(): void
    {
        $optionId = $this->createCheckoutOption('delivery');
        $this->createCart('delivery-update-session');

        $response = $this->updateOrderOption('delivery-update-session', $optionId);

        $this->assertSame(0, $response['status']);
        $this->assertSame('Please select a valid delivery address.', $response['message']);
        $this->assertArrayHasKey('deliveryAddressId', $response['errors']);
    }

    public function test_updating_to_delivery_recomputes_the_fee_for_the_selected_address(): void
    {
        config([
            'services.google.maps_key' => null,
            'services.delivery.rate' => 50,
            'services.delivery.additional_km_rate' => 10,
        ]);

        $optionId = $this->createCheckoutOption('delivery');
        $addressId = DB::table('user_address')->insertGetId([
            'user_id' => $this->user->id,
            'active' => true,
            'lat' => 10.3257000,
            'long' => 123.8954000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cartId = $this->createCart('delivery-fee-session');
        $this->createCartItem($cartId);

        $response = $this->updateOrderOption('delivery-fee-session', $optionId, $addressId);

        $this->assertSame(1, $response['status']);
        $this->assertSame('delivery', $response['cart']['fulfillment_type']);
        $this->assertSame($addressId, $response['cart']['address_id']);
        $this->assertNotSame('0.00', $response['summary']['delivery_fee']);
        $this->assertSame(
            (float) $response['summary']['delivery_fee'],
            (float) DB::table('cart')->where('id', $cartId)->value('delivery_fee')
        );
    }

    private function createCheckoutOption(string $type): int
    {
        return DB::table('partner_location_checkout_options')->insertGetId([
            'partner_location_id' => $this->locationId,
            'type' => $type,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function submitCheckout(string $sessionId, int $optionId, array $extra = []): array
    {
        $this->createCart($sessionId);

        $request = Request::create('/api/mobile/checkout/submit', 'POST', array_merge([
            'session_id' => $sessionId,
            'deliveryDate' => '2026-09-29',
            'deliveryTime' => '14:32:39',
            'deliveryAddressId' => '',
            'deliveryPaymentId' => 1,
            'partnerOrderOptionId' => $optionId,
        ], $extra));
        $request->setUserResolver(fn () => $this->user);

        return (new CheckoutController)->process($request)->getData(true);
    }

    private function createCart(string $sessionId, float $deliveryFee = 0): int
    {
        return DB::table('cart')->insertGetId([
            'user_id' => $this->user->id,
            'session_id' => $sessionId,
            'partner_id' => $this->partnerId,
            'partner_location_address_id' => $this->locationId,
            'fulfillment_type' => 'delivery',
            'delivery_fee' => $deliveryFee,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCartItem(int $cartId): void
    {
        DB::table('cart_details')->insert([
            'cart_id' => $cartId,
            'qty' => 1,
            'price' => 100,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function updateOrderOption(string $sessionId, int $optionId, ?int $deliveryAddressId = null): array
    {
        $payload = [
            'session_id' => $sessionId,
            'partnerOrderOptionId' => $optionId,
        ];

        if ($deliveryAddressId !== null) {
            $payload['deliveryAddressId'] = $deliveryAddressId;
        }

        $request = Request::create('/api/mobile/checkout/order-option/update/submit', 'POST', $payload);
        $request->setUserResolver(fn () => $this->user);

        return (new CheckoutController)->updateOrderOption($request)->getData(true);
    }
}
