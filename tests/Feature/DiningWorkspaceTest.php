<?php

namespace Tests\Feature;

use App\DiningBill;
use App\DiningBillOrder;
use App\DiningPayment;
use App\Services\DiningRoom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\DiningFixtures;
use Tests\TestCase;

class DiningWorkspaceTest extends TestCase
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

    private function paymentInput(DiningBill $bill): array
    {
        $total = app(DiningRoom::class)->totals($bill)['total'];

        return [
            'payment_method_id' => 3,
            'idempotency_key' => (string) Str::uuid(),
            'expected_total' => $total,
            'cash_tendered_cents' => $total,
        ];
    }

    public function test_existing_owner_dashboard_and_order_apis_still_load_dining_orders(): void
    {
        $order = $this->diningOrder(status: 'ready');
        $this->actingAs($this->accounts['owner'])->get('/merchant/dashboard')->assertOk()->assertSee('Cashier workspace')->assertSee('Kitchen display')->assertSee('Store settings');
        $this->getJson('/api/merchant/order/list')->assertOk()->assertJsonPath('orders.0.id', $order->id);
        $this->getJson('/api/merchant/dashboard/order/summary')->assertOk()->assertJsonPath('record.totalOrders', 1);
    }

    public function test_disabled_qr_ordering_removes_and_blocks_dining_tools(): void
    {
        DB::table('partners')->where('id', 1)->update(['qr_ordering_enabled' => false]);

        $this->actingAs($this->accounts['owner'])->get('/merchant/dashboard')
            ->assertOk()
            ->assertSee('Store settings')
            ->assertDontSee('Cashier workspace')
            ->assertDontSee('Kitchen display')
            ->assertSee('Dining tables');
        $this->get('/merchant/tables')
            ->assertOk()
            ->assertSee(":qr-ordering-enabled='false'", false);
        $this->get('/merchant/staff')->assertForbidden();
    }

    public function test_submitted_rounds_share_one_bill_and_are_visible_in_both_views(): void
    {
        $first = $this->diningOrder();
        $second = $this->diningOrder(qty: 2);
        $private = $this->diningOrder(20);
        $this->assertSame($first->billRound->dining_bill_id, $second->billRound->dining_bill_id);
        $this->assertSame(2, $second->billRound->round);
        app(DiningRoom::class)->attach($first);
        $this->assertSame(3, DiningBillOrder::count());
        foreach (['kitchen', 'cashier'] as $role) {
            $response = $this->actingAs($this->accounts[$role])->getJson('/merchant/staff/data?branch_id=2')->assertOk();
            $this->assertEqualsCanonicalizing([$first->id, $second->id], array_column($response->json('orders'), 'id'));
            $response->assertDontSee($private->cart->order_no);
            if ($role === 'kitchen') {
                $response->assertJsonCount(0, 'bills')
                    ->assertJsonCount(0, 'payments')
                    ->assertJsonPath('orders.0.total', null)
                    ->assertJsonPath('orders.0.items.0.variant_options.0', 'Extra rice');
            } else {
                $response->assertJsonCount(1, 'bills')->assertJsonPath('bills.0.totals.qty', 3);
            }
        }
    }

    public function test_customer_payment_preference_is_exposed_for_orders_and_combined_bills(): void
    {
        $first = $this->diningOrder();
        $second = $this->diningOrder();
        DB::table('cart')->whereIn('id', [$first->cart_id, $second->cart_id])->update(['payment_id' => 2]);

        $response = $this->actingAs($this->accounts['cashier'])->getJson('/merchant/staff/data')->assertOk();
        $response->assertJsonPath('orders.0.payment_method_id', 2)
            ->assertJsonPath('orders.0.payment_method', 'GCash')
            ->assertJsonPath('bills.0.payment_method_id', 2)
            ->assertJsonPath('bills.0.payment_method', 'GCash');

        DB::table('cart')->where('id', $second->cart_id)->update(['payment_id' => 1]);

        $this->getJson('/merchant/staff/data')
            ->assertOk()
            ->assertJsonPath('bills.0.payment_method_id', null)
            ->assertJsonPath('bills.0.payment_method', 'Mixed preferences');
    }

    public function test_kitchen_transitions_are_scoped_audited_and_cannot_skip_steps(): void
    {
        $order = $this->diningOrder();
        $private = $this->diningOrder(20);
        $this->actingAs($this->accounts['kitchen'])->postJson("/merchant/staff/orders/{$order->id}/transition", ['action' => 'served', 'expected_status' => 1])->assertConflict();
        $this->postJson("/merchant/staff/orders/{$private->id}/transition", ['action' => 'preparing', 'expected_status' => 1])->assertNotFound();
        foreach ([['preparing', 1, 3], ['ready', 3, 4], ['served', 4, 9]] as [$action, $previous, $target]) {
            $this->postJson("/merchant/staff/orders/{$order->id}/transition", ['action' => $action, 'expected_status' => $previous])->assertOk();
            $this->assertSame($target, (int) $order->fresh()->order_status_id);
            $this->assertDatabaseHas('order_process', ['order_id' => $order->id, 'status_id' => $target, 'user_id' => $this->accounts['kitchen']->id]);
            $this->postJson("/merchant/staff/orders/{$order->id}/transition", ['action' => $action, 'expected_status' => $previous])->assertOk();
        }
        $this->getJson('/merchant/staff/data')->assertOk()->assertJsonPath('orders.0.status', 'served');
        $this->assertNotNull($order->fresh()->delivered_at);
        $this->assertSame('open', DiningBill::first()->status);
    }

    public function test_cashier_can_serve_but_cannot_prepare_and_kitchen_cannot_record_payments(): void
    {
        $order = $this->diningOrder(status: 'ready');
        $bill = DiningBill::first();
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/orders/{$order->id}/transition", ['action' => 'preparing', 'expected_status' => 4])->assertForbidden();
        $this->postJson("/merchant/staff/orders/{$order->id}/transition", ['action' => 'served', 'expected_status' => 4])->assertOk();
        $this->actingAs($this->accounts['kitchen'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $this->paymentInput($bill))->assertForbidden();
        $this->getJson("/merchant/staff/bills/{$bill->id}/receipt")->assertForbidden();
    }

    public function test_payment_requires_served_orders_and_the_reviewed_total(): void
    {
        $order = $this->diningOrder();
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertConflict();
        foreach (['preparing', 'ready', 'served'] as $action) {
            app(DiningRoom::class)->transition($order->id, 1, 1, $action, $this->accounts['kitchen']->id);
        }
        $input['expected_total']++;
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertConflict();
        $this->assertSame(0, DiningPayment::count());
        $this->assertSame('open', $bill->fresh()->status);
    }

    public function test_payment_is_idempotent_and_the_next_seating_gets_a_new_bill(): void
    {
        $order = $this->diningOrder(status: 'served');
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", array_replace($input, ['idempotency_key' => (string) Str::uuid()]))->assertConflict();
        $this->assertSame(1, DiningPayment::count());
        $this->assertSame('paid', $bill->fresh()->status);
        $this->assertSame($this->accounts['cashier']->id, (int) DiningPayment::first()->recorded_by_user_id);
        $newOrder = $this->diningOrder();
        $this->assertNotSame($bill->id, $newOrder->billRound->dining_bill_id);
        $this->assertSame(1, $newOrder->billRound->round);
        $this->getJson('/merchant/staff/data')->assertJsonCount(1, 'bills')->assertJsonCount(1, 'payments');
    }

    public function test_cash_payment_requires_enough_tender_and_records_change(): void
    {
        $this->diningOrder(status: 'served');
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        $input['cash_tendered_cents'] = $input['expected_total'] - 1;

        $this->actingAs($this->accounts['cashier'])
            ->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cash tendered must cover the total due.');

        $input['cash_tendered_cents'] = $input['expected_total'] + 500;
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();

        $this->assertDatabaseHas('dining_payments', [
            'dining_bill_id' => $bill->id,
            'amount_cents' => $input['expected_total'],
            'cash_tendered_cents' => $input['cash_tendered_cents'],
            'change_cents' => 500,
        ]);
        $this->getJson("/merchant/staff/bills/{$bill->id}/receipt")
            ->assertOk()
            ->assertJsonPath('payment.cash_tendered', $input['cash_tendered_cents'])
            ->assertJsonPath('payment.change', 500);
    }

    public function test_new_round_invalidates_a_cashiers_reviewed_total(): void
    {
        $this->diningOrder(status: 'served');
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        $this->diningOrder(status: 'served');
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertConflict();
        $this->assertSame(0, DiningPayment::count());
    }

    public function test_receipt_preserves_item_and_price_snapshots_and_is_branch_scoped(): void
    {
        $order = $this->diningOrder(status: 'served');
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        DB::table('products')->where('id', 1)->update(['title' => 'Changed menu name', 'price' => 999]);
        config(['checkout.convenience_fee_rate' => 0.50]);
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
        $this->getJson("/merchant/staff/bills/{$bill->id}/receipt")->assertOk()->assertJsonPath('paid', true)
            ->assertJsonPath('totals.total', $input['expected_total'])->assertJsonPath('orders.0.items.0.name', 'Davao Chicken Inasal')->assertJsonPath('payment.method', 'Cash');
        $private = $this->diningOrder(20, status: 'served')->billRound->bill;
        $this->getJson("/merchant/staff/bills/{$private->id}/receipt")->assertNotFound();
        $this->postJson("/merchant/staff/bills/{$private->id}/payment", $this->paymentInput($private))->assertNotFound();
    }

    public function test_cancelled_orders_are_excluded_from_payment_and_inactive_methods_are_rejected(): void
    {
        $cancelled = $this->diningOrder();
        $this->diningOrder(status: 'served');
        DB::table('order')->where('id', $cancelled->id)->update(['order_status_id' => 8]);
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        $this->assertSame(1, app(DiningRoom::class)->totals($bill)['qty']);
        DB::table('payment_method')->where('id', 3)->update(['active' => false]);
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertNotFound();
        DB::table('payment_method')->where('id', 3)->update(['active' => true]);
        $this->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
    }

    public function test_only_fully_cancelled_bills_can_be_released_without_a_payment(): void
    {
        $order = $this->diningOrder();
        $bill = DiningBill::first();
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/release")->assertConflict();
        DB::table('order')->where('id', $order->id)->update(['order_status_id' => 8]);
        $this->postJson("/merchant/staff/bills/{$bill->id}/release")->assertOk();
        $this->assertSame('void', $bill->fresh()->status);
        $this->assertSame(0, DiningPayment::count());
        $this->assertNotSame($bill->id, $this->diningOrder()->billRound->dining_bill_id);
    }

    public function test_paid_receipts_survive_later_order_status_changes(): void
    {
        $order = $this->diningOrder(status: 'served');
        $bill = DiningBill::first();
        $input = $this->paymentInput($bill);
        $this->actingAs($this->accounts['cashier'])->postJson("/merchant/staff/bills/{$bill->id}/payment", $input)->assertOk();
        DB::table('order')->where('id', $order->id)->update(['order_status_id' => 8]);
        $this->getJson("/merchant/staff/bills/{$bill->id}/receipt")->assertOk()->assertJsonCount(1, 'orders')
            ->assertJsonPath('totals.total', $input['expected_total']);
    }

    public function test_owners_can_select_branches_and_cannot_select_another_merchants_branch(): void
    {
        $order = $this->diningOrder(20);
        $this->actingAs($this->accounts['owner'])->getJson('/merchant/staff/data?branch_id=2')->assertOk()->assertJsonPath('orders.0.id', $order->id);
        $this->getJson('/merchant/staff/data?branch_id=999')->assertNotFound();
    }

    public function test_operational_table_availability_is_scoped_and_restricted(): void
    {
        $this->actingAs($this->accounts['kitchen'])->patchJson('/merchant/staff/tables/1', ['is_available' => false])->assertForbidden();
        $this->actingAs($this->accounts['cashier'])->patchJson('/merchant/staff/tables/20', ['is_available' => false])->assertNotFound();
        $this->patchJson('/merchant/staff/tables/1', ['is_available' => false])->assertOk();
        $this->assertDatabaseHas('partner_location_tables', ['id' => 1, 'is_available' => false]);
        $this->assertDatabaseHas('partner_location_tables', ['id' => 20, 'is_available' => true]);
    }
}
