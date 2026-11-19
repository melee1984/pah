<?php

namespace Tests\Feature;

use App\DiningPayment;
use App\Model\Orders\Orders;
use App\Services\DiningRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\DiningFixtures;
use Tests\TestCase;

class CounterServiceTest extends TestCase
{
    use DiningFixtures, RefreshDatabase;

    private array $accounts;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->createDiningDomainSchema();
        $this->accounts = $this->seedDiningAccounts();
    }

    private function pay(Orders $order): void
    {
        $bill = $order->billRound->bill;
        $total = app(DiningRoom::class)->totals($bill)['total'];
        $input = ['payment_method_id' => 3, 'idempotency_key' => (string) Str::uuid(), 'expected_total' => $total, 'cash_tendered_cents' => $total];
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
        $input['idempotency_key'] = (string) Str::uuid();
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertConflict();
    }

    private function move(Orders $order, string $action): void
    {
        app(DiningRoom::class)->transition($order->id, 1, $order->cart->partner_location_address_id, $action, $this->accounts['kitchen']->id);
    }

    public function test_pay_first_keeps_preparing_orders_live_until_served_and_preserves_receipt_status(): void
    {
        $order = $this->diningOrder();
        $this->pay($order);
        $this->assertSame(1, DiningPayment::count());
        $this->assertSame(1, (int) $order->fresh()->order_status_id);
        $this->assertNull($order->fresh()->delivered_at);
        $this->move($order, 'preparing');
        $this->actingAs($this->accounts['kitchen'])->getJson('/merchant/staff/data')->assertOk()->assertJsonPath('orders.0.paid', true)->assertJsonPath('orders.0.status', 'preparing')->assertJsonPath('orders.0.served_at', null);
        $this->move($order, 'ready');
        $this->travel(6)->minutes();
        $this->move($order, 'served');
        $this->getJson('/merchant/staff/data')->assertJsonCount(0, 'orders');
        $history = $this->getJson('/merchant/staff/history')->assertOk()->assertJsonPath('orders.0.paid', true)->assertJsonPath('orders.0.status', 'served')->assertJsonPath('orders.0.time_to_serve_seconds', 360);
        $this->assertNotNull($history->json('orders.0.paid_at'));
        $this->assertNotNull($history->json('orders.0.served_at'));
        $this->actingAs($this->accounts['cashier'])->getJson('/merchant/staff/bills/'.$order->billRound->dining_bill_id.'/receipt')->assertOk()->assertJsonPath('paid', true)->assertJsonPath('orders.0.status', 'served');
        $this->assertSame(1, DiningPayment::count());
    }

    public function test_serve_first_stays_unpaid_on_board_until_collection_without_changing_served_time(): void
    {
        $order = $this->diningOrder(status: 'served');
        $served = $order->delivered_at;
        $this->actingAs($this->accounts['kitchen'])->getJson('/merchant/staff/data')->assertOk()->assertJsonPath('orders.0.status', 'served')->assertJsonPath('orders.0.paid', false);
        $this->travel(10)->minutes();
        $this->pay($order);
        $this->assertEquals($served, $order->fresh()->delivered_at);
        $this->assertSame(9, (int) $order->fresh()->order_status_id);
        $this->getJson('/merchant/staff/data')->assertJsonCount(0, 'orders');
        $this->getJson('/merchant/staff/history')->assertOk()->assertJsonPath('orders.0.paid', true)->assertJsonPath('orders.0.status', 'served');
    }

    public function test_in_store_pos_blocks_delivery_pickup_and_exposes_only_dine_in(): void
    {
        $this->actingAs($this->accounts['cashier'])->getJson('/merchant/staff/data')->assertOk()->assertJsonCount(1, 'fulfillment_types')->assertJsonPath('fulfillment_types.0.type', 'dine_in');
        foreach (['delivery', 'pickup'] as $type) {
            $input = ['idempotency_key' => (string) Str::uuid(), 'fulfillment_type' => $type, 'payment_method_id' => 3, 'items' => [['product_id' => 1, 'qty' => 1]]];
            $this->postJson('/merchant/staff/orders', $input)->assertUnprocessable();
            $this->postJson('/merchant/staff/pos/quote', $input)->assertUnprocessable();
        }
        $this->assertSame(0, Orders::count());
    }

    public function test_unpaid_served_orders_from_previous_days_remain_live(): void
    {
        $order = $this->diningOrder(status: 'served');
        DB::table('order')->where('id', $order->id)->update(['submitted_at' => now()->subDays(3)]);
        $this->actingAs($this->accounts['kitchen'])->getJson('/merchant/staff/data')->assertOk()->assertJsonPath('orders.0.id', $order->id)->assertJsonPath('orders.0.paid', false);
    }

    public function test_fastest_served_history_is_branch_and_date_scoped_and_ignores_missing_or_invalid_times(): void
    {
        $slow = $this->diningOrder(status: 'served');
        $fast = $this->diningOrder(status: 'served');
        $unknown = $this->diningOrder(status: 'served');
        $invalid = $this->diningOrder(status: 'served');
        $private = $this->diningOrder(20, status: 'served');
        $outside = $this->diningOrder(status: 'served');
        foreach ([[$slow, 600], [$fast, 60], [$private, 1], [$outside, 2]] as [$order, $seconds]) {
            $placed = $order->id === $outside->id ? now()->subDays(10) : now()->subMinutes(20);
            DB::table('order')->where('id', $order->id)->update(['submitted_at' => $placed, 'delivered_at' => $placed->copy()->addSeconds($seconds)]);
        }
        DB::table('order')->where('id', $unknown->id)->update(['delivered_at' => null]);
        DB::table('order')->where('id', $invalid->id)->update(['delivered_at' => now()->subDay()]);
        $date = now()->toDateString();
        $response = $this->actingAs($this->accounts['owner'])->getJson("/merchant/staff/history?from=$date&to=$date&sort=fastest")->assertOk()->assertJsonCount(2, 'orders')->assertJsonPath('orders.0.id', $fast->id)->assertJsonPath('orders.0.time_to_serve_seconds', 60)->assertJsonPath('orders.1.id', $slow->id);
        $this->assertSame(2, $response->json('total'));
        $this->getJson("/merchant/staff/history?branch_id=2&from=$date&to=$date&sort=fastest")->assertOk()->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $private->id);
        $all = $this->getJson("/merchant/staff/history?from=$date&to=$date")->assertOk();
        $rows = collect($all->json('orders'))->keyBy('id');
        $this->assertNull($rows[$unknown->id]['time_to_serve_seconds']);
        $this->assertNull($rows[$invalid->id]['time_to_serve_seconds']);
        $this->actingAs($this->accounts['cashier'])->getJson('/merchant/staff/history?sort=fastest')->assertForbidden();
        $this->actingAs($this->accounts['kitchen'])->getJson('/merchant/staff/history?branch_id=2')->assertOk()->assertDontSee($private->cart->order_no);
    }

    public function test_a_populated_legacy_delivery_field_does_not_invent_service_before_staff_serve(): void
    {
        $order = $this->diningOrder();
        DB::table('order')->where('id', $order->id)->update(['delivered_at' => now()->addMinute()]);
        $this->actingAs($this->accounts['owner'])->getJson('/merchant/staff/history')->assertOk()->assertJsonPath('orders.0.served_at', null)->assertJsonPath('orders.0.time_to_serve_seconds', null);
        $this->getJson('/merchant/staff/history?sort=fastest')->assertOk()->assertJsonCount(0, 'orders');
    }

    public function test_paid_preparing_round_and_new_unpaid_round_share_the_table_without_sharing_payment(): void
    {
        $paid = $this->diningOrder();
        $this->pay($paid);
        $new = $this->diningOrder();
        $this->assertNotSame($paid->billRound->dining_bill_id, $new->billRound->dining_bill_id);
        $response = $this->getJson('/merchant/staff/data')->assertOk()->assertJsonCount(2, 'orders');
        $tickets = collect($response->json('orders'))->keyBy('id');
        $this->assertTrue($tickets[$paid->id]['paid']);
        $this->assertFalse($tickets[$new->id]['paid']);
        foreach (['preparing', 'ready', 'served'] as $action) {
            $this->move($paid, $action);
        }
        $this->getJson('/merchant/staff/data')->assertOk()->assertJsonCount(1, 'orders')->assertJsonPath('orders.0.id', $new->id)->assertJsonPath('orders.0.paid', false);
    }

    public function test_missing_placement_is_not_invented_and_delivery_time_is_not_used_as_serving_time(): void
    {
        $order = $this->diningOrder(status: 'served');
        DB::table('order')->where('id', $order->id)->update(['submitted_at' => null]);
        $delivery = $this->diningOrder(status: 'served');
        DB::table('cart')->where('id', $delivery->cart_id)->update(['fulfillment_type' => 'delivery', 'dining_table_id' => null]);
        $response = $this->actingAs($this->accounts['owner'])->getJson('/merchant/staff/history')->assertOk();
        $rows = collect($response->json('orders'))->keyBy('id');
        $this->assertNull($rows[$order->id]['placed_at']);
        $this->assertNull($rows[$order->id]['time_to_serve_seconds']);
        $this->assertNull($rows[$delivery->id]['served_at']);
        $this->assertNull($rows[$delivery->id]['time_to_serve_seconds']);
    }
}
