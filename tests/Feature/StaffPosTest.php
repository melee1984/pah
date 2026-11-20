<?php

namespace Tests\Feature;

use App\DiningBill;
use App\DiningPayment;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\PartnerLocationCheckoutOption;
use App\ProductLocationAvailability;
use App\Services\DiningRoom;
use App\Services\RiderOfferDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\DiningFixtures;
use Tests\TestCase;

class StaffPosTest extends TestCase
{
    use DiningFixtures, RefreshDatabase;

    private array $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->createDiningDomainSchema();
        $this->accounts = $this->seedDiningAccounts();
        DB::table('product_item_header')->insert(['id' => 1, 'product_id' => 1, 'title' => 'Add-ons', 'is_multiple' => true, 'is_required' => true]);
        DB::table('product_item_details')->insert(['id' => 1, 'product_header_id' => 1, 'title' => 'Extra rice', 'price' => 25]);
    }

    private function input(string $type = 'dine_in'): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(), 'fulfillment_type' => $type,
            'dining_table_id' => $type === 'dine_in' ? 1 : null, 'payment_method_id' => 3,
            'fullname' => 'Walk-in', 'mobile' => '09170000000', 'address' => 'Davao address',
            'lat' => 7.07, 'long' => 125.6, 'delivery_fee' => 50, 'notes' => 'Pack cutlery separately',
            'items' => [['product_id' => 1, 'qty' => 2, 'instruction' => 'No onions', 'selections' => [['variant_id' => 1, 'product_detail_id' => 1]]]],
        ];
    }

    private function legacyChannelOrder(string $type): Orders
    {
        $cart = Cart::create(['partner_id' => 1, 'session_id' => (string) Str::uuid(), 'partner_location_address_id' => 1,
            'fulfillment_type' => $type, 'order_source' => Cart::ORDER_SOURCE_POS, 'payment_id' => 3]);
        $cart->forceFill(['fullname' => 'Walk-in'])->save();
        $cart->details()->create(['item_id' => 1, 'qty' => 1, 'price' => 165]);

        return Orders::create(['cart_id' => $cart->id, 'partner_id' => 1, 'submitted_at' => now(), 'order_status_id' => 1]);
    }

    public function test_in_store_pos_reuses_existing_orders_items_bills_and_payments(): void
    {
        foreach ([Cart::FULFILLMENT_DINE_IN, Cart::FULFILLMENT_PICKUP] as $type) {
            $input = $this->input($type);
            if ($type === Cart::FULFILLMENT_PICKUP) {
                unset($input['payment_method_id']);
            }
            $response = $this->actingAs($this->accounts['cashier'])->postJson('/merchant/staff/orders', $input)->assertCreated();
            $this->postJson('/merchant/staff/orders', $input)->assertCreated()->assertJsonPath('order_id', $response->json('order_id'));
            $order = Orders::findOrFail($response->json('order_id'));
            $this->assertSame('pos', $order->cart->order_source);
            $this->assertSame($type, $order->cart->fulfillment_type);
            $this->assertSame($type === Cart::FULFILLMENT_PICKUP ? null : 3, $order->cart->payment_id);
            $this->assertSame($type === 'dine_in' ? 1 : null, $order->cart->dining_table_id);
            $this->assertSame(19000, $order->billRound->items[0]['unit_price']);
            $this->assertSame('Extra rice Add-ons', $order->billRound->items[0]['variant_options'][0]);
            $this->assertSame('No onions', $order->billRound->items[0]['note']);
            $bill = $order->billRound->bill;
            $this->actingAs($this->accounts['kitchen'])->getJson('/merchant/staff/data')
                ->assertOk()->assertJsonCount(0, 'menu')->assertJsonCount(0, 'payments');
            DB::table('order')->where('id', $order->id)->update(['order_status_id' => $type === 'delivery' ? 4 : 9]);
            $total = app(DiningRoom::class)->totals($bill)['total'];
            $payment = ['payment_method_id' => 3, 'idempotency_key' => (string) Str::uuid(), 'expected_total' => $total, 'cash_tendered_cents' => $total + 500];
            $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $payment)->assertOk();
            $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $payment)->assertOk();
            $this->assertSame('paid', $bill->fresh()->status);
            $this->getJson("/merchant/staff/bills/{$bill->id}/receipt")->assertOk()->assertJsonPath('payment.change', 500);
            if ($type === 'delivery') {
                $this->assertSame(4, (int) $order->fresh()->order_status_id);
                $this->assertSame('Davao address', $order->cart->address->address_1);
            }
        }
        $this->assertSame(2, Orders::count());
        $this->assertSame(2, DiningPayment::count());
    }

    public function test_incoming_customer_orders_of_all_types_have_complete_branch_scoped_kitchen_details(): void
    {
        foreach ([1, 2] as $branch) {
            foreach (Cart::FULFILLMENT_TYPES as $type) {
                $cart = Cart::create(['session_id' => (string) Str::uuid(), 'partner_id' => 1, 'partner_location_address_id' => $branch,
                    'fulfillment_type' => $type, 'dining_table_id' => $type === 'dine_in' ? ($branch === 1 ? 1 : 20) : null,
                    'notes' => "Whole order $type"]);
                $cart->details()->create(['item_id' => 1, 'qty' => 2, 'price' => 165, 'instruction' => 'No onions', 'variance_content' => serialize([['title' => 'Extra rice']])]);
                Orders::create(['cart_id' => $cart->id, 'partner_id' => 1, 'submitted_at' => now(), 'order_status_id' => 1]);
            }
        }
        $response = $this->actingAs($this->accounts['kitchen'])->getJson('/merchant/staff/data?branch_id=2')->assertOk()->assertJsonCount(3, 'orders');
        $this->assertEqualsCanonicalizing(Cart::FULFILLMENT_TYPES, array_column($response->json('orders'), 'type'));
        foreach ($response->json('orders') as $ticket) {
            $this->assertSame('Whole order '.$ticket['type'], $ticket['notes']);
            $this->assertSame('No onions', $ticket['items'][0]['note']);
            $this->assertSame(['Extra rice'], $ticket['items'][0]['variant_options']);
            $this->assertSame(2, $ticket['items'][0]['qty']);
            $this->assertSame('Davao Chicken Inasal', $ticket['items'][0]['name']);
            $this->assertArrayNotHasKey('unit_price', $ticket['items'][0]);
            $this->assertNotEmpty($ticket['created']);
            $this->assertNotEmpty($ticket['number']);
            $this->assertSame('new', $ticket['status']);
            $this->assertSame($ticket['type'] === 'dine_in' ? 'Table 01' : ucfirst($ticket['type']), $ticket['table_name']);
        }
    }

    public function test_pos_rejects_unauthorized_roles_foreign_tables_and_unavailable_or_invalid_items_atomically(): void
    {
        $input = $this->input();
        $this->actingAs($this->accounts['kitchen'])->postJson('/merchant/staff/orders', $input)->assertForbidden();
        $this->get('/merchant/staff?view=pos')->assertForbidden();
        $this->actingAs($this->accounts['cashier'])->postJson('/merchant/staff/orders?branch_id=2', array_replace($input, ['dining_table_id' => 20]))->assertNotFound();
        $input['items'][0]['selections'] = [];
        $this->postJson('/merchant/staff/orders', $input)->assertUnprocessable();
        $input = $this->input();
        $input['items'][0]['selections'][0]['product_detail_id'] = 999;
        $this->postJson('/merchant/staff/orders', $input)->assertUnprocessable();
        DB::table('products')->where('id', 1)->update(['active' => false]);
        $this->postJson('/merchant/staff/orders', $this->input())->assertUnprocessable();
        $this->assertSame(0, Cart::count());
        $this->assertSame(0, Orders::count());
        $this->assertSame(0, DiningBill::count());
    }

    public function test_pos_bill_payment_and_receipts_cannot_cross_branch_boundaries(): void
    {
        $this->actingAs($this->accounts['owner'])->postJson('/merchant/staff/orders?branch_id=2', array_replace($this->input(), ['dining_table_id' => 20]))->assertCreated();
        $bill = DiningBill::first();
        $this->actingAs($this->accounts['cashier'])->getJson("/merchant/staff/bills/{$bill->id}/receipt?branch_id=2")->assertNotFound();
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment?branch_id=2", ['idempotency_key' => (string) Str::uuid(), 'payment_method_id' => 3, 'expected_total' => 0])->assertNotFound();
        $this->getJson('/merchant/staff/data?branch_id=2')->assertJsonCount(0, 'orders');
    }

    public function test_pos_replay_cannot_silently_replace_a_changed_order(): void
    {
        $input = $this->input();
        $this->actingAs($this->accounts['cashier'])->postJson('/merchant/staff/orders', $input)->assertCreated();
        $input['items'][0]['qty']++;
        $this->postJson('/merchant/staff/orders', $input)->assertConflict();
        $this->assertSame(1, Orders::count());
    }

    public function test_customer_checkout_settings_do_not_hide_counter_service_types(): void
    {
        PartnerLocationCheckoutOption::create(['partner_location_id' => 1, 'type' => 'pickup', 'active' => false]);
        PartnerLocationCheckoutOption::create(['partner_location_id' => 1, 'type' => 'dine_in', 'active' => false]);

        $this->actingAs($this->accounts['cashier'])->getJson('/merchant/staff/data')->assertOk()
            ->assertJsonPath('fulfillment_types.0.type', 'dine_in')
            ->assertJsonPath('fulfillment_types.1.type', 'pickup');
        $this->postJson('/merchant/staff/orders', $this->input())->assertCreated();
        $this->postJson('/merchant/staff/orders', $this->input('pickup'))->assertCreated();
        $this->assertSame(2, Orders::count());
    }

    public function test_pickup_transitions_reuse_merchant_flow_and_delivery_cannot_be_marked_served(): void
    {
        $id = $this->legacyChannelOrder('pickup')->id;
        $this->actingAs($this->accounts['kitchen']);
        foreach ([['preparing', 1, 3], ['ready', 3, 4], ['served', 4, 9]] as [$action, $before, $after]) {
            $this->postJson("/merchant/staff/orders/$id/transition", ['action' => $action, 'expected_status' => $before])->assertOk();
            $this->assertDatabaseHas('order', ['id' => $id, 'order_status_id' => $after]);
            $this->assertDatabaseHas('order_process', ['order_id' => $id, 'status_id' => $after, 'user_id' => $this->accounts['kitchen']->id]);
        }
        $id = $this->legacyChannelOrder('delivery')->id;
        DB::table('order')->where('id', $id)->update(['order_status_id' => 4]);
        $this->actingAs($this->accounts['kitchen'])->postJson("/merchant/staff/orders/$id/transition", ['action' => 'served', 'expected_status' => 4])->assertConflict();
    }

    public function test_cashier_delivery_payment_clears_cod_before_handoff_and_is_blocked_after_handoff(): void
    {
        foreach (['offered', 'picked_up'] as $state) {
            $this->actingAs($this->accounts['cashier']);
            $order = $this->legacyChannelOrder('delivery');
            DB::table('order')->where('id', $order->id)->update(['order_status_id' => 4]);
            $bill = $order->billRound->bill;
            $total = app(DiningRoom::class)->totals($bill)['total'];
            $delivery = DB::table('rider_api_deliveries')->insertGetId(['reference' => (string) Str::uuid(), 'legacy_order_id' => $order->id, 'current_state' => $state, 'cod_centavos' => $total, 'created_at' => now(), 'updated_at' => now()]);
            $response = $this->postJson("/merchant/staff/bills/{$bill->id}/payment", ['payment_method_id' => 3, 'idempotency_key' => (string) Str::uuid(), 'expected_total' => $total, 'cash_tendered_cents' => $total]);
            if ($state === 'offered') {
                $response->assertOk();
                $this->assertDatabaseHas('rider_api_deliveries', ['id' => $delivery, 'cod_centavos' => 0]);
            } else {
                $response->assertConflict();
                $this->assertSame('open', $bill->fresh()->status);
                $this->assertDatabaseHas('rider_api_deliveries', ['id' => $delivery, 'cod_centavos' => $total]);
            }
        }
    }

    public function test_dispatch_created_after_cashier_payment_has_no_cod_to_collect(): void
    {
        $this->actingAs($this->accounts['cashier']);
        $order = $this->legacyChannelOrder('delivery');
        DB::table('order')->where('id', $order->id)->update(['order_status_id' => 4]);
        $bill = $order->billRound->bill;
        $total = app(DiningRoom::class)->totals($bill)['total'];
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", ['payment_method_id' => 3, 'idempotency_key' => (string) Str::uuid(), 'expected_total' => $total, 'cash_tendered_cents' => $total])->assertOk();
        app(RiderOfferDispatcher::class)->dispatchOrder($order->fresh());
        $this->assertDatabaseHas('rider_api_deliveries', ['legacy_order_id' => $order->id, 'cod_centavos' => 0, 'customer_name' => 'Walk-in']);
    }

    public function test_pos_quote_matches_saved_order_and_does_not_write_drafts(): void
    {
        foreach ([Cart::FULFILLMENT_DINE_IN, Cart::FULFILLMENT_PICKUP] as $type) {
            $input = $this->input($type);
            $input['items'][0]['price'] = 1;
            $before = Cart::count();
            $quote = $this->actingAs($this->accounts['cashier'])->postJson('/merchant/staff/pos/quote', $input)->assertOk();
            $quote->assertJsonPath('totals.sub_total', 38000)->assertJsonPath('totals.qty', 2);
            $this->assertSame($before, Cart::count());
            $created = $this->postJson('/merchant/staff/orders', $input)->assertCreated();
            $order = Orders::findOrFail($created->json('order_id'));
            $this->assertSame($order->billRound->dining_bill_id, $created->json('bill_id'));
            $this->assertSame($quote->json('totals.total'), $order->billRound->amounts['total']);
            $this->assertSame($quote->json('totals.discount'), $order->billRound->amounts['discount']);
        }
    }

    public function test_pos_quote_enforces_role_branch_menu_and_modifier_permissions(): void
    {
        $this->actingAs($this->accounts['kitchen'])->postJson('/merchant/staff/pos/quote', $this->input())->assertForbidden();
        $input = $this->input();
        $input['items'][0]['selections'] = [];
        $this->actingAs($this->accounts['cashier'])->postJson('/merchant/staff/pos/quote', $input)->assertUnprocessable();
        ProductLocationAvailability::create(['product_id' => 1, 'partner_location_id' => 1, 'active' => false]);
        $this->postJson('/merchant/staff/pos/quote?branch_id=2', $this->input())->assertUnprocessable();
        $this->assertSame(0, Cart::count());
    }

    public function test_pos_menu_uses_existing_categories_descriptions_and_optional_photos(): void
    {
        DB::table('category')->insert(['id' => 1, 'user_id' => $this->accounts['owner']->id, 'name' => 'Mains']);
        DB::table('products')->where('id', 1)->update(['category_id' => 1, 'short_description' => '<p>Grilled fresh</p>']);
        $this->actingAs($this->accounts['cashier'])->getJson('/merchant/staff/data')->assertOk()
            ->assertJsonPath('menu.0.category', 'Mains')->assertJsonPath('menu.0.description', 'Grilled fresh')->assertJsonPath('menu.0.image', null);
        DB::table('products')->where('id', 1)->update(['img' => 'meal.jpg']);
        $response = $this->getJson('/merchant/staff/data')->assertOk();
        $this->assertStringContainsString('imager?name=1/meal.jpg', $response->json('menu.0.image'));
    }

    public function test_shared_sidebar_shows_only_authorized_staff_links(): void
    {
        $this->actingAs($this->accounts['kitchen'])->get('/merchant/staff?view=kitchen')->assertOk()
            ->assertSee('merchant-sidebar-navigation')->assertSee('Kitchen display')->assertDontSee('Cashier workspace')->assertDontSee('Store settings');
        $this->actingAs($this->accounts['cashier'])->get('/merchant/staff')->assertOk()
            ->assertSee('merchant-sidebar-navigation')->assertSee('Cashier workspace')->assertSee('Kitchen display')->assertDontSee('Branch users');
    }
}
