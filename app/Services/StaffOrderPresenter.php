<?php

namespace App\Services;

use App\Model\Cart;
use App\Model\Orders\Orders;
use App\PaymentMethod;
use Carbon\Carbon;

class StaffOrderPresenter
{
    public function ticket(Orders $order, array $abilities): array
    {
        $cart = $order->cart;
        $round = $order->billRound;
        $statusId = (int) ($order->order_status_id ?: $order->status_id);
        $items = $round?->items ?? app(DiningRoom::class)->items($cart);
        if (! $abilities['cashier']) {
            $items = array_map(fn ($item) => array_diff_key($item, ['unit_price' => true]), $items);
        }
        // For dine-in/pickup this timestamp is written by the staff serving action.
        // Delivery uses it for arrival at the customer and is not a serving metric.
        $placed = $order->submitted_at ? Carbon::parse($order->submitted_at) : null;
        $served = in_array($cart->fulfillment_type, [Cart::FULFILLMENT_DINE_IN, Cart::FULFILLMENT_PICKUP], true)
            && $order->delivered_at
            && ($statusId === 9 || $order->orderprocess->contains('status_id', 9))
            ? Carbon::parse($order->delivered_at) : null;
        $seconds = $placed && $served && $served->greaterThanOrEqualTo($placed)
            ? (int) $placed->diffInSeconds($served) : null;
        $payment = $round?->bill?->payment;

        return [
            'id' => $order->id, 'number' => $cart->order_no ?: (string) $order->id,
            'table_id' => $cart->dining_table_id, 'table_name' => $cart->diningTable?->name ?? ucfirst($cart->fulfillment_type ?: 'delivery'),
            'branch_id' => $cart->partner_location_address_id,
            'type' => $cart->fulfillment_type ?: Cart::FULFILLMENT_DELIVERY,
            'notes' => $cart->notes, 'source' => $cart->order_source,
            'created' => $placed?->toIso8601String(), 'placed_at' => $placed?->toIso8601String(),
            'served_at' => $served?->toIso8601String(), 'paid_at' => $payment?->created_at?->toIso8601String(),
            'time_to_serve_seconds' => $seconds,
            'status_id' => $statusId,
            'status' => match ($statusId) {
                1 => 'new', 2, 3 => 'preparing', 4 => 'ready', 5 => 'on_the_way', 6 => 'arrived', 7, 9 => 'served', 8 => 'cancelled', default => 'new'
            },
            'round' => $round?->round ?? 1, 'bill_id' => $round?->dining_bill_id,
            'paid' => (bool) $payment, 'items' => $items,
            'payment_method_id' => $cart->payment_id,
            'payment_method' => $cart->payment ? ((int) $cart->payment_id === PaymentMethod::CHECKOUT_COD ? 'Cash' : $cart->payment->title) : null,
            'total' => $abilities['cashier'] ? ($round?->amounts['total'] ?? (int) round($cart->cartItemAmounts()['total'] * 100)) : null,
        ];
    }
}
