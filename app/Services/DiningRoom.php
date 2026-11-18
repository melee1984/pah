<?php

namespace App\Services;

use App\DiningBill;
use App\DiningBillOrder;
use App\DiningPayment;
use App\LibraryStatus;
use App\Model\Cart;
use App\Model\Orders\OrderProcess;
use App\Model\Orders\Orders;
use App\PartnerLocationTable;
use App\PaymentMethod;
use Illuminate\Support\Facades\DB;

class DiningRoom
{
    public function attach(Orders $order): ?DiningBillOrder
    {
        $cart = $order->cart;
        if (! $order->submitted_at || $cart?->fulfillment_type !== Cart::FULFILLMENT_DINE_IN || ! $cart->dining_table_id) {
            return null;
        }

        return DB::transaction(function () use ($order, $cart) {
            // The table row serializes new rounds against payment/closing.
            $table = PartnerLocationTable::whereKey($cart->dining_table_id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $table->partner_location_id === (int) $cart->partner_location_address_id
                && (int) $cart->partner_id === (int) $order->partner_id
                && (int) $table->location->partner_id === (int) $order->partner_id, 409, 'Order table and branch do not match.');

            if ($existing = DiningBillOrder::where('order_id', $order->id)->first()) {
                return $existing;
            }

            $bill = DiningBill::firstOrCreate([
                'dining_table_id' => $table->id, 'status' => 'open',
            ], [
                'partner_id' => $order->partner_id,
                'partner_location_id' => $table->partner_location_id,
            ]);

            $amounts = [];
            foreach ($cart->cartItemAmounts() as $key => $value) {
                $amounts[$key] = $key === 'qty' ? (int) $value : (int) round($value * 100);
            }

            return DiningBillOrder::create([
                'dining_bill_id' => $bill->id, 'order_id' => $order->id,
                'round' => ((int) $bill->rounds()->max('round')) + 1,
                'amounts' => $amounts, 'items' => $this->items($cart),
            ]);
        });
    }

    public function items(Cart $cart): array
    {
        return $cart->details->map(function ($item) {
            $variants = $item->variance_content;
            if (is_string($variants)) {
                $variants = @unserialize($variants, ['allowed_classes' => false]);
            }
            $names = [];
            $variants = is_array($variants) ? $variants : [];
            array_walk_recursive($variants, function ($value, $key) use (&$names) {
                if (in_array($key, ['title', 'name', 'label'], true) && is_string($value)) {
                    $names[] = $value;
                }
            });

            return [
                'name' => $item->item?->title ?? 'Unavailable menu item',
                'qty' => (int) $item->qty,
                'unit_price' => (int) round(((float) $item->price + (float) $item->variance_total) * 100),
                'note' => $item->instruction,
                'variants' => implode(', ', array_unique($names)),
            ];
        })->all();
    }

    public function transition(int $orderId, int $partnerId, int $branchId, string $action, int $actorId, ?int $expected = null): Orders
    {
        return DB::transaction(function () use ($orderId, $partnerId, $branchId, $action, $actorId, $expected) {
            $order = Orders::whereKey($orderId)->where('partner_id', $partnerId)
                ->whereHas('cart', fn ($query) => $query->where('partner_location_address_id', $branchId)->where('fulfillment_type', Cart::FULFILLMENT_DINE_IN))
                ->lockForUpdate()->firstOrFail();
            [$target, $allowed] = match ($action) {
                'preparing' => [LibraryStatus::STATUS_PROCESSING, [1, 2]],
                'ready' => [LibraryStatus::STATUS_READY_FOR_PICKUP, [2, 3]],
                'served' => [LibraryStatus::STATUS_COMPLETED, [4]],
            };
            $status = (int) ($order->order_status_id ?: $order->status_id);
            if ($status === $target) {
                return $order;
            }
            abort_if($expected !== null && $expected !== $status, 409, 'This order changed. Refresh and try again.');
            abort_unless(in_array($status, $allowed, true), 409, 'This order cannot move to that status.');
            $order->order_status_id = $target;
            $order->booking_status_id = null;
            $order->accepted_by_store_id = $branchId;
            $order->store_accepted_at = $order->store_accepted_at ?: now();
            if ($action === 'served') {
                $order->delivered_at = now();
            }
            $order->save();
            if ($status === LibraryStatus::STATUS_ORDER_PLACED && $target === LibraryStatus::STATUS_PROCESSING) {
                OrderProcess::firstOrCreate(['order_id' => $order->id, 'status_id' => LibraryStatus::STATUS_ORDER_ACCEPTED], ['user_id' => $actorId]);
            }
            OrderProcess::firstOrCreate(['order_id' => $order->id, 'status_id' => $target], ['user_id' => $actorId]);

            return $order;
        });
    }

    public function totals(DiningBill $bill): array
    {
        $totals = array_fill_keys(['sub_total', 'delivery_fee', 'convenience_fee', 'vat_amount', 'discount', 'total', 'qty'], 0);
        foreach ($bill->rounds as $round) {
            if (! $round->order || (int) $round->order->order_status_id === LibraryStatus::STATUS_CANCELLED) {
                continue;
            }
            foreach ($totals as $key => $value) {
                $totals[$key] += $round->amounts[$key] ?? 0;
            }
        }

        return $totals;
    }

    public function pay(int $billId, int $partnerId, int $branchId, int $actorId, array $input): DiningPayment
    {
        return DB::transaction(function () use ($billId, $partnerId, $branchId, $actorId, $input) {
            $bill = DiningBill::whereKey($billId)->where('partner_id', $partnerId)->where('partner_location_id', $branchId)->firstOrFail();
            PartnerLocationTable::whereKey($bill->dining_table_id)->lockForUpdate()->firstOrFail();
            $bill = DiningBill::whereKey($bill->id)->lockForUpdate()->firstOrFail();
            $existing = DiningPayment::where('idempotency_key', $input['idempotency_key'])->first();
            if ($existing) {
                abort_unless((int) $existing->dining_bill_id === (int) $bill->id
                    && (int) $existing->payment_method_id === (int) $input['payment_method_id']
                    && (int) $existing->amount_cents === (int) $input['expected_total']
                    && ($existing->reference ?? '') === ($input['reference'] ?? ''), 409, 'Payment reference has already been used.');

                return $existing;
            }
            abort_unless($bill->status === 'open', 409, 'This bill is already closed.');
            $rounds = $bill->rounds()->get();
            $orders = Orders::whereIn('id', $rounds->pluck('order_id'))->lockForUpdate()->get()->keyBy('id');
            $rounds->each(fn ($round) => $round->setRelation('order', $orders->get($round->order_id)));
            $bill->setRelation('rounds', $rounds);
            $payable = $orders->where('order_status_id', '!=', LibraryStatus::STATUS_CANCELLED);
            abort_if($payable->isEmpty() || $payable->contains(fn ($order) => (int) $order->order_status_id !== LibraryStatus::STATUS_COMPLETED), 409, 'Serve all orders before closing this bill.');
            abort_if($rounds->count() !== $orders->count(), 409, 'An order is missing from this bill.');
            $totals = $this->totals($bill);
            abort_unless($totals['total'] === (int) $input['expected_total'], 409, 'The bill total changed. Review it before recording payment.');
            $method = PaymentMethod::active()->forQr()->findOrFail($input['payment_method_id']);
            $payment = DiningPayment::create([
                'dining_bill_id' => $bill->id, 'idempotency_key' => $input['idempotency_key'],
                'recorded_by_user_id' => $actorId, 'payment_method_id' => $method->id,
                'method_name' => (int) $method->id === PaymentMethod::CHECKOUT_COD ? 'Cash' : $method->title, 'amount_cents' => $totals['total'],
                'reference' => $input['reference'] ?? null,
            ]);
            $receipt = $rounds->filter(fn ($round) => $round->order && (int) $round->order->order_status_id !== LibraryStatus::STATUS_CANCELLED)
                ->map(fn ($round) => ['id' => $round->order_id, 'number' => $round->order->cart?->order_no ?: (string) $round->order_id, 'round' => $round->round, 'items' => $round->items])->values()->all();
            $bill->update(['status' => 'paid', 'totals' => $totals, 'receipt' => $receipt, 'closed_at' => now()]);

            return $payment;
        });
    }
}
