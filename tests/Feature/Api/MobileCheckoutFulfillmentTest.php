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

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('session_id');
            $table->unsignedBigInteger('partner_location_address_id');
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->string('fulfillment_type')->nullable();
            $table->unsignedBigInteger('dining_table_id')->nullable();
            $table->string('discount_code')->nullable();
            $table->dateTime('sms_code_validated_at')->nullable();
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
            $table->timestamps();
        });

        $this->locationId = DB::table('partner_location')->insertGetId([
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
        DB::table('cart')->insert([
            'user_id' => $this->user->id,
            'session_id' => $sessionId,
            'partner_location_address_id' => $this->locationId,
            'fulfillment_type' => 'delivery',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
}
