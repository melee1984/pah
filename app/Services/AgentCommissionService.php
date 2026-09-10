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
            $breakdown = $this->orderBreakdown($order);
            $percentage = round((float) $agent->commission_percentage, 2);
            $pahatudCommissionPercentage = round((float) ($restaurant->percentage ?? config('agent.pahatud_commission_percentage')), 2);
            $pahatudCommissionAmount = round($breakdown['subtotal'] * ($pahatudCommissionPercentage / 100), 2);
            $commissionAmount = round($pahatudCommissionAmount * ($percentage / 100), 2);

            return AgentCommission::query()->create([
                'order_id' => $order->getKey(),
                'restaurant_id' => $restaurant->getKey(),
                'agent_id' => $agent->getKey(),
                'order_amount' => $breakdown['total'],
                'subtotal_amount' => $breakdown['subtotal'],
                'delivery_fee_amount' => $breakdown['delivery_fee'],
                'discount_amount' => $breakdown['discount'],
                'total_amount' => $breakdown['total'],
                'pahatud_commission_percentage' => $pahatudCommissionPercentage,
                'pahatud_commission_amount' => $pahatudCommissionAmount,
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

    /**
     * @return array{subtotal: float, delivery_fee: float, discount: float, total: float}
     */
    private function orderBreakdown(Orders $order): array
    {
        if (! $order->cart) {
            return [
                'subtotal' => 0,
                'delivery_fee' => 0,
                'discount' => 0,
                'total' => 0,
            ];
        }

        $items = DB::table('cart_details')
            ->where('cart_id', $order->cart->getKey())
            ->selectRaw('COALESCE(SUM(qty * (price + variance_total)), 0) as subtotal')
            ->selectRaw('COALESCE(SUM(qty * discount_amount), 0) as item_discount')
            ->first();

        $subtotal = round((float) $items->subtotal, 2);
        $deliveryFee = round((float) $order->cart->delivery_fee, 2);
        $discount = round((float) $order->cart->discount_amount + (float) $items->item_discount, 2);

        return [
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'discount' => $discount,
            'total' => round($subtotal + $deliveryFee - $discount, 2),
        ];
    }

    private function isCancelled(Orders $order): bool
    {
        return (int) $order->order_status_id === LibraryStatus::STATUS_CANCELLED;
    }
}
