<?php

namespace Tests\Feature\Api;

use App\Events\SendPushNotificationEvent;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class QrOrderingTest extends TestCase
{
    use RefreshDatabase;

    private int $branchId;

    private int $partnerId;

    private int $productId;

    private int $tableId;

    private string $qrUuid;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('mobile')->nullable();
            $table->unsignedBigInteger('account_type_id')->nullable();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('restaurant_name');
            $table->text('description')->nullable();
            $table->string('mobile')->nullable();
            $table->string('img')->nullable();
            $table->string('banner')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('store_open')->default(true);
            $table->boolean('addup')->default(false);
            $table->decimal('pahatud_discount', 8, 2)->nullable();
            $table->decimal('pahatud_comm_discount', 8, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('address_1')->nullable();
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('mobile')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_category_id')->nullable();
            $table->string('name');
            $table->string('identifier')->nullable();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('title');
            $table->string('short_description')->nullable();
            $table->text('description')->nullable();
            $table->string('slug')->nullable();
            $table->decimal('price', 8, 2);
            $table->decimal('markdown_price', 8, 2)->nullable();
            $table->decimal('price_comm', 8, 2)->default(0);
            $table->decimal('markdown_price_comm', 8, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->string('img')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('product_location_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('partner_location_id')->index();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['product_id', 'partner_location_id']);
        });

        Schema::create('product_item_header', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_multiple')->default(false);
            $table->unsignedInteger('sorting')->default(0);
            $table->timestamps();
        });

        Schema::create('product_item_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_header_id');
            $table->string('title');
            $table->decimal('price', 8, 2)->default(0);
            $table->decimal('price_comm', 8, 2)->default(0);
            $table->decimal('markdown_price', 8, 2)->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('payment_method', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('class')->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('available_in_application')->default(true);
            $table->boolean('available_in_qr')->default(true);
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('session_id');
            $table->string('fullname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->string('order_no')->nullable();
            $table->boolean('active')->default(false);
            $table->string('ip_address')->nullable();
            $table->dateTime('processed_at')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->string('discount_code')->nullable();
            $table->decimal('delivery_fee', 8, 2)->default(0);
            $table->decimal('distance_rate', 8, 2)->default(0);
            $table->string('duration')->nullable();
            $table->string('origin')->nullable();
            $table->string('destination')->nullable();
            $table->string('delivery_date')->nullable();
            $table->string('delivery_time')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedBigInteger('partner_location_address_id')->nullable();
            $table->string('fulfillment_type')->default('delivery');
            $table->unsignedBigInteger('dining_table_id')->nullable();
            $table->string('order_source', 40)->nullable();
            $table->unsignedBigInteger('qr_ordering_code_id')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('item_id');
            $table->decimal('price', 8, 2);
            $table->unsignedInteger('qty')->default(0);
            $table->decimal('variance_total', 8, 2)->default(0);
            $table->text('variance_content')->nullable();
            $table->unsignedTinyInteger('is_not_available')->default(0);
            $table->text('instruction')->nullable();
            $table->decimal('price_comm_total', 8, 2)->default(0);
            $table->decimal('variance_total_comm_total', 8, 2)->default(0);
            $table->decimal('discount_amount', 8, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('cart_id');
            $table->dateTime('submitted_at')->nullable();
            $table->unsignedInteger('order_status_id')->nullable();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->timestamps();
        });

        Schema::create('order_process', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('status_id');
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'Restaurant Owner',
            'firstname' => 'Restaurant',
            'lastname' => 'Owner',
            'email' => 'restaurant@example.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->partnerId = DB::table('partners')->insertGetId([
            'user_id' => $userId,
            'restaurant_name' => 'QR Test Kitchen',
            'description' => 'Dine-in menu',
            'active' => true,
            'store_open' => true,
            'addup' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->branchId = DB::table('partner_location')->insertGetId([
            'partner_id' => $this->partnerId,
            'address_1' => 'Main Branch',
            'city' => 'Cebu City',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->tableId = DB::table('partner_location_tables')->insertGetId([
            'partner_location_id' => $this->branchId,
            'name' => 'Table 7',
            'capacity' => 4,
            'active' => true,
            'is_available' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $categoryId = DB::table('category')->insertGetId([
            'user_id' => $userId,
            'name' => 'Mains',
            'identifier' => 'mains',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->productId = DB::table('products')->insertGetId([
            'user_id' => $userId,
            'category_id' => $categoryId,
            'title' => 'Chicken Meal',
            'price' => 120,
            'price_comm' => 0,
            'markdown_price_comm' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payment_method')->insert([
            'id' => 3,
            'title' => 'Pay at counter',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->qrUuid = (string) Str::uuid();
        DB::table('qr_ordering_codes')->insert([
            'uuid' => $this->qrUuid,
            'partner_id' => $this->partnerId,
            'partner_location_id' => $this->branchId,
            'dining_table_id' => $this->tableId,
            'label' => 'Table 7',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_qr_menu_is_scoped_to_its_restaurant_and_branch(): void
    {
        $this->getJson($this->baseUrl().'/menu')
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('restaurant.id', $this->partnerId)
            ->assertJsonPath('branch.id', $this->branchId)
            ->assertJsonPath('table.id', $this->tableId)
            ->assertJsonPath('categories.0.name', 'Mains')
            ->assertJsonPath('menu.0.id', $this->productId)
            ->assertJsonPath('menu.0.price', '120.00')
            ->assertJsonPath('menu.0.original_price', '120.00')
            ->assertJsonPath('menu.0.promo_price', null);

        $this->getJson("/api/qr/{$this->qrUuid}/branches/999/menu")->assertNotFound();
    }

    public function test_qr_menu_exposes_original_and_promotional_prices(): void
    {
        DB::table('products')->where('id', $this->productId)->update([
            'markdown_price' => 90,
        ]);

        $this->getJson($this->baseUrl().'/menu')
            ->assertOk()
            ->assertJsonPath('menu.0.price', '90.00')
            ->assertJsonPath('menu.0.original_price', '120.00')
            ->assertJsonPath('menu.0.promo_price', '90.00');
    }

    public function test_application_only_payment_method_is_hidden_and_rejected_by_qr_ordering(): void
    {
        DB::table('payment_method')->where('id', 3)->update([
            'available_in_application' => true,
            'available_in_qr' => false,
        ]);

        $this->getJson($this->baseUrl().'/menu')
            ->assertOk()
            ->assertJsonCount(0, 'payment_methods');

        $session = $this->createQrSession();

        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->postJson($this->baseUrl()."/carts/{$session['session_id']}/checkout", [
                'full_name' => 'Guest Customer',
                'mobile' => '09171234567',
                'payment_method_id' => 3,
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('payment_method_id');
    }

    public function test_guest_can_add_to_cart_and_fetch_summary(): void
    {
        $session = $this->createQrSession();
        $sessionId = $session['session_id'];

        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->postJson($this->baseUrl()."/carts/{$sessionId}/items", [
                'product_id' => $this->productId,
                'quantity' => 2,
                'instruction' => 'No onions',
            ])->assertCreated()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('data.items.0.quantity', 2)
            ->assertJsonPath('data.items.0.instruction', 'No onions');

        $this->getJson($this->baseUrl()."/carts/{$sessionId}/summary")
            ->assertOk()
            ->assertJsonPath('data.customer', null)
            ->assertJsonPath('data.summary.qty', 2)
            ->assertJsonPath('data.summary.sub_total', '240.00')
            ->assertJsonPath('data.branch_id', $this->branchId);
    }

    public function test_guest_can_checkout_without_a_user_account(): void
    {
        Event::fake([SendPushNotificationEvent::class]);
        $session = $this->createQrSession();
        $sessionId = $session['session_id'];

        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->postJson($this->baseUrl()."/carts/{$sessionId}/items", [
                'product_id' => $this->productId,
            ])->assertCreated();

        $this->postJson($this->baseUrl()."/carts/{$sessionId}/checkout", [
            'full_name' => 'Guest Customer',
            'mobile' => '09171234567',
            'email' => 'guest@example.com',
            'payment_method_id' => 3,
        ])->assertCreated()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('order.fulfillment_type', 'dine_in')
            ->assertJsonPath('order.order_source', 'qr_ordering')
            ->assertJsonPath('order.table.id', $this->tableId)
            ->assertJsonPath('order.summary.qty', 1);

        $this->assertDatabaseHas('cart', [
            'session_id' => $sessionId,
            'fullname' => 'Guest Customer',
            'partner_location_address_id' => $this->branchId,
            'dining_table_id' => $this->tableId,
            'fulfillment_type' => 'dine_in',
            'order_source' => 'qr_ordering',
            'qr_ordering_code_id' => DB::table('qr_ordering_codes')->where('uuid', $this->qrUuid)->value('id'),
            'active' => true,
        ]);
        $this->assertDatabaseHas('order', [
            'user_id' => null,
            'partner_id' => $this->partnerId,
            'order_status_id' => 1,
        ]);
        $this->assertDatabaseHas('dining_bills', ['partner_id' => $this->partnerId, 'partner_location_id' => $this->branchId, 'dining_table_id' => $this->tableId, 'status' => 'open']);
        $this->assertDatabaseHas('dining_bill_orders', ['order_id' => DB::table('order')->value('id'), 'round' => 1]);

        $this->getJson($this->baseUrl()."/carts/{$sessionId}/summary")
            ->assertOk()
            ->assertJsonPath('data.customer.full_name', 'Guest Customer')
            ->assertJsonPath('data.customer.mobile', '09171234567');

        Event::assertDispatched(SendPushNotificationEvent::class);
    }

    public function test_guest_can_view_the_order_for_their_qr_session(): void
    {
        Event::fake([SendPushNotificationEvent::class]);
        $session = $this->createQrSession();
        $base = $this->baseUrl()."/carts/{$session['session_id']}";

        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->getJson($base.'/orders')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonCount(0, 'orders');

        $this->postJson($base.'/items', [
            'product_id' => $this->productId,
            'quantity' => 2,
            'instruction' => 'No onions',
        ])->assertCreated();

        $this->postJson($base.'/checkout', [
            'full_name' => 'Guest Customer',
            'mobile' => '09171234567',
            'payment_method_id' => 3,
        ])->assertCreated();

        $this->getJson($base.'/orders')
            ->assertOk()
            ->assertJsonPath('session_id', $session['session_id'])
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.customer_name', 'Guest Customer')
            ->assertJsonPath('orders.0.status.label', 'Received')
            ->assertJsonPath('orders.0.status.is_final', false)
            ->assertJsonPath('orders.0.table.id', $this->tableId)
            ->assertJsonPath('orders.0.payment_method.title', 'Pay at counter')
            ->assertJsonPath('orders.0.items.0.quantity', 2)
            ->assertJsonPath('orders.0.items.0.instruction', 'No onions')
            ->assertJsonPath('orders.0.summary.sub_total', '240.00');

        DB::table('order')->update(['order_status_id' => 9]);

        $this->getJson($base.'/orders')
            ->assertOk()
            ->assertJsonPath('orders.0.status.label', 'Served')
            ->assertJsonPath('orders.0.status.is_final', true);
    }

    public function test_qr_cart_rejects_a_product_from_another_restaurant(): void
    {
        $otherProductId = DB::table('products')->insertGetId([
            'user_id' => 999,
            'title' => 'Other Restaurant Item',
            'price' => 50,
            'price_comm' => 0,
            'markdown_price_comm' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $session = $this->createQrSession();
        $sessionId = $session['session_id'];
        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->postJson($this->baseUrl()."/carts/{$sessionId}/items", [
                'product_id' => $otherProductId,
            ])->assertUnprocessable()
            ->assertJsonPath('status', 0);

        $this->assertDatabaseMissing('cart_details', ['item_id' => $otherProductId]);
    }

    public function test_qr_cart_requires_a_server_issued_session_token(): void
    {
        $session = $this->createQrSession();

        $this->getJson($this->baseUrl()."/carts/{$session['session_id']}/summary")
            ->assertUnauthorized()
            ->assertJsonPath('status', 0);
    }

    public function test_qr_session_token_cannot_access_another_cart_session(): void
    {
        $first = $this->createQrSession();
        $second = $this->createQrSession();

        $this->withHeader('X-QR-Session-Token', $first['token'])
            ->getJson($this->baseUrl()."/carts/{$second['session_id']}/summary")
            ->assertUnauthorized()
            ->assertJsonPath('status', 0);
    }

    public function test_qr_session_token_expires(): void
    {
        config(['services.qr_ordering.session_ttl_minutes' => 5]);
        $session = $this->createQrSession();
        $this->travel(6)->minutes();

        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->getJson($this->baseUrl()."/carts/{$session['session_id']}/summary")
            ->assertUnauthorized()
            ->assertJsonPath('status', 0);
    }

    public function test_qr_cart_rejects_more_than_one_hundred_total_items(): void
    {
        $session = $this->createQrSession();
        $url = $this->baseUrl()."/carts/{$session['session_id']}/items";

        $this->withHeader('X-QR-Session-Token', $session['token'])
            ->postJson($url, ['product_id' => $this->productId, 'quantity' => 100])
            ->assertCreated();

        $this->postJson($url, ['product_id' => $this->productId, 'quantity' => 1])
            ->assertConflict()
            ->assertJsonPath('status', 0);
    }

    public function test_merchant_can_regenerate_a_table_qr_code(): void
    {
        $merchant = User::query()->where('email', 'restaurant@example.com')->firstOrFail();

        $response = $this->actingAs($merchant)->postJson(
            "/api/merchant/location/{$this->branchId}/qr-ordering-codes",
            [
                'dining_table_id' => $this->tableId,
                'label' => 'Table 7 QR',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('message', 'QR ordering code regenerated.')
            ->assertJsonPath('qr_code.table.id', $this->tableId);

        $this->assertNotSame($this->qrUuid, $response->json('qr_code.uuid'));
        $this->assertDatabaseCount('qr_ordering_codes', 1);
        $this->assertDatabaseHas('qr_ordering_codes', [
            'partner_location_id' => $this->branchId,
            'dining_table_id' => $this->tableId,
            'label' => 'Table 7 QR',
            'active' => true,
        ]);
    }

    public function test_creating_a_dining_table_automatically_creates_its_qr_url(): void
    {
        config(['services.qr_ordering.url' => 'https://qr.pahatud.test/order']);
        $merchant = User::query()->where('email', 'restaurant@example.com')->firstOrFail();

        $response = $this->actingAs($merchant)->postJson(
            "/api/merchant/location/{$this->branchId}/tables",
            [
                'name' => 'Table 8',
                'capacity' => 6,
                'active' => true,
                'is_available' => true,
            ]
        );

        $response->assertCreated()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('table.name', 'Table 8');

        $tableId = $response->json('table.id');
        $qrUuid = $response->json('table.qr_code.uuid');
        $orderingUrl = $response->json('table.qr_code.ordering_url');

        $this->assertNotEmpty($qrUuid);
        $this->assertSame(
            "https://qr.pahatud.test/order?qr_uuid={$qrUuid}&partner_id={$this->partnerId}&branch_id={$this->branchId}&table_id={$tableId}",
            $orderingUrl
        );
        $this->assertDatabaseHas('qr_ordering_codes', [
            'uuid' => $qrUuid,
            'partner_id' => $this->partnerId,
            'partner_location_id' => $this->branchId,
            'dining_table_id' => $tableId,
            'label' => 'Table 8',
            'active' => true,
        ]);
    }

    public function test_pausing_a_table_blocks_new_items_and_checkout_but_keeps_the_menu_readable(): void
    {
        $session = $this->createQrSession();
        $url = $this->baseUrl()."/carts/{$session['session_id']}";
        $this->withHeader('X-QR-Session-Token', $session['token'])->postJson($url.'/items', ['product_id' => $this->productId])->assertCreated();
        DB::table('partner_location_tables')->where('id', $this->tableId)->update(['is_available' => false]);
        $this->postJson($url.'/items', ['product_id' => $this->productId])->assertConflict();
        $this->postJson($url.'/checkout', ['full_name' => 'Guest', 'mobile' => '09171234567', 'payment_method_id' => 3])->assertConflict();
        $this->getJson($this->baseUrl().'/menu')->assertOk();
        $this->assertDatabaseCount('order', 0);
    }

    private function baseUrl(): string
    {
        return "/api/qr/{$this->qrUuid}/branches/{$this->branchId}";
    }

    private function createQrSession(): array
    {
        return $this->postJson($this->baseUrl().'/sessions')
            ->assertCreated()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertJsonPath('status', 1)
            ->json('session');
    }
}
