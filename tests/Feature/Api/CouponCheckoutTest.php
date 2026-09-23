<?php

namespace Tests\Feature\Api;

use App\Coupon;
use App\Http\Controllers\Api\Mobile\CheckoutController as MobileCheckoutController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CouponCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('coupon', function (Blueprint $table) {
            $table->id();
            $table->string('coupon');
            $table->dateTime('valid_from')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->dateTime('valid_at')->nullable();
            $table->integer('limit')->nullable();
            $table->boolean('active')->nullable();
            $table->decimal('discount_value', 8, 2)->nullable();
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->decimal('condition', 8, 2)->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->timestamps();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->timestamps();
        });
        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->integer('item_id')->nullable();
            $table->integer('qty');
            $table->decimal('price', 8, 2);
            $table->decimal('variance_total', 8, 2)->default(0);
            $table->decimal('price_comm_total', 8, 2)->default(0);
            $table->decimal('variance_total_comm_total', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->dateTime('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function test_percentage_coupon_for_the_cart_partner_is_applied_to_checkout(): void
    {
        Carbon::setTestNow('2026-09-23 12:00:00');
        $this->startSession();
        session()->put('coupon_test', true);
        session()->save();
        $cartId = DB::table('cart')->insertGetId([
            'session_id' => session()->getId(),
            'partner_id' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cart_details')->insert([
            'cart_id' => $cartId,
            'qty' => 2,
            'price' => 100,
            'variance_total' => 0,
            'discount_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Coupon::create([
            'coupon' => 'SAVE20',
            'partner_id' => 12,
            'discount_percentage' => 20,
            'condition' => 150,
            'valid_from' => now()->subHour(),
            'valid_until' => now()->addHour(),
            'active' => true,
        ]);

        $response = $this->withCredentials()->withCookie(config('session.cookie'), session()->getId())
            ->postJson('/api/checkout/coupon/submit', ['coupon' => 'save20']);

        $response->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('coupon.scope', 'partner')
            ->assertJsonPath('coupon.discount_percentage', 20)
            ->assertJsonPath('discount_amount', 40);
        $this->assertDatabaseHas('cart', [
            'id' => $cartId,
            'discount_code' => 'SAVE20',
            'discount_amount' => 40,
        ]);
    }

    public function test_coupon_for_another_partner_cannot_be_applied(): void
    {
        $this->startSession();
        session()->put('coupon_test', true);
        session()->save();
        DB::table('cart')->insert([
            'session_id' => session()->getId(),
            'partner_id' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Coupon::create([
            'coupon' => 'OTHERSTORE',
            'partner_id' => 99,
            'discount_value' => 50,
            'active' => true,
        ]);

        $this->withCredentials()->withCookie(config('session.cookie'), session()->getId())
            ->postJson('/api/checkout/coupon/submit', ['coupon' => 'OTHERSTORE'])
            ->assertOk()
            ->assertJsonPath('status', 0);
    }

    public function test_mobile_coupon_is_applied_using_the_provided_session_id(): void
    {
        $cartId = DB::table('cart')->insertGetId([
            'session_id' => 'mobile-session-id',
            'partner_id' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('cart_details')->insert([
            'cart_id' => $cartId,
            'qty' => 2,
            'price' => 100,
            'variance_total' => 0,
            'discount_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Coupon::create([
            'coupon' => 'MOBILE20',
            'partner_id' => 12,
            'discount_percentage' => 20,
            'active' => true,
        ]);

        $request = Request::create('/api/mobile/checkout/coupon/submit', 'POST', [
            'session_id' => 'mobile-session-id',
            'coupon' => 'mobile20',
        ]);
        $response = (new MobileCheckoutController)->couponCode($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $response->getData(true)['status']);
        $this->assertEquals(40.0, $response->getData(true)['discount_amount']);
        $this->assertDatabaseHas('cart', [
            'id' => $cartId,
            'discount_code' => 'MOBILE20',
            'discount_amount' => 40,
        ]);
    }

    public function test_available_coupon_endpoint_returns_global_and_current_partner_coupons_only(): void
    {
        $this->startSession();
        session()->put('coupon_test', true);
        session()->save();
        DB::table('cart')->insert([
            'session_id' => session()->getId(),
            'partner_id' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        foreach ([
            ['coupon' => 'GLOBAL', 'partner_id' => null],
            ['coupon' => 'CURRENT', 'partner_id' => 12],
            ['coupon' => 'OTHER', 'partner_id' => 99],
        ] as $data) {
            Coupon::create(array_merge($data, ['discount_value' => 10, 'active' => true]));
        }

        $response = $this->withCredentials()->withCookie(config('session.cookie'), session()->getId())
            ->getJson('/api/checkout/coupons');

        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing(['GLOBAL', 'CURRENT'], $response->json('data.*.code'));
    }

    public function test_coupon_cannot_be_applied_after_its_usage_limit_is_reached(): void
    {
        $this->startSession();
        session()->save();

        $usedCartId = DB::table('cart')->insertGetId([
            'session_id' => 'previous-order',
            'partner_id' => 12,
            'discount_code' => 'ONCEONLY',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('order')->insert([
            'cart_id' => $usedCartId,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cart')->insert([
            'session_id' => session()->getId(),
            'partner_id' => 12,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Coupon::create([
            'coupon' => 'ONCEONLY',
            'partner_id' => 12,
            'discount_value' => 50,
            'limit' => 1,
            'active' => true,
        ]);

        $this->withCredentials()->withCookie(config('session.cookie'), session()->getId())
            ->postJson('/api/checkout/coupon/submit', ['coupon' => 'ONCEONLY'])
            ->assertOk()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'This coupon has reached its usage limit.');
    }
}
