<?php

namespace App\Services;

use App\AgentCommission;
use App\LibraryStatus;
use App\Model\Orders\Orders;
use Illuminate\Support\Facades\DB;

class AgentCommissionService
{
    public function sync(Orders $order): ?AgentCommission
    {
        if ($this->isCancelled($order)) {
            return $this->reverse($order, 'Order was cancelled, refunded, or failed.');
        }

        if (! $this->isDelivered($order)) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            $existing = AgentCommission::query()
                ->where('order_id', $order->getKey())
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $restaurant = $order->partner()->with('agent')->first();
            $agent = $restaurant?->agent;

            if (! $restaurant || ! $agent) {
                return null;
            }

            $order->loadMissing('cart');
            $orderAmount = $this->orderAmount($order);
            $percentage = round((float) $agent->commission_percentage, 2);
            $commissionAmount = round($orderAmount * ($percentage / 100), 2);

            return AgentCommission::query()->create([
                'order_id' => $order->getKey(),
                'restaurant_id' => $restaurant->getKey(),
                'agent_id' => $agent->getKey(),
                'order_amount' => $orderAmount,
                'commission_percentage' => $percentage,
                'commission_amount' => $commissionAmount,
                'status' => AgentCommission::STATUS_PENDING,
                'qualified_at' => $order->delivered_at ?? now(),
            ]);
        });
    }

    private function reverse(Orders $order, string $reason): ?AgentCommission
    {
        $commission = AgentCommission::query()->where('order_id', $order->getKey())->first();

        if (! $commission || $commission->status === AgentCommission::STATUS_REVERSED) {
            return $commission;
        }

        $commission->update([
            'status' => AgentCommission::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => $reason,
        ]);

        return $commission;
    }

    private function isDelivered(Orders $order): bool
    {
        return (int) $order->order_status_id === LibraryStatus::STATUS_DELIVERED
            || $order->delivered_at !== null;
    }

    private function orderAmount(Orders $order): float
    {
        if (! $order->cart) {
            return 0;
        }

        $items = DB::table('cart_details')
            ->where('cart_id', $order->cart->getKey())
            ->selectRaw('COALESCE(SUM(qty * (price + variance_total)), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(qty * discount_amount), 0) as item_discount')
            ->first();

        return round(
            (float) $items->subtotal
            + (float) $order->cart->delivery_fee
            - (float) $order->cart->discount_amount
            - (float) $items->item_discount,
            2,
        );
    }

    private function isCancelled(Orders $order): bool
    {
        return (int) $order->order_status_id === LibraryStatus::STATUS_CANCELLED;
    }
}
