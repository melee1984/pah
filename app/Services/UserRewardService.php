<?php

namespace App\Services;

use App\LibraryStatus;
use App\Model\Cart;
use App\Model\Orders\Orders;
use App\UserReward;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserRewardService
{
    public function sync(Orders $order): ?UserReward
    {
        if (! config('rewards.enabled') || ! Schema::hasTable('user_rewards')) {
            return null;
        }

        if ($this->isCancelled($order)) {
            return $this->reverse($order);
        }

        if (! $this->isDelivered($order) || ! $order->user_id) {
            return null;
        }

        return DB::transaction(function () use ($order) {
            Orders::query()->whereKey($order->getKey())->lockForUpdate()->first();

            $existing = UserReward::query()->where('order_id', $order->getKey())->first();

            if ($existing) {
                return $existing;
            }

            $cart = Cart::query()->find($order->cart_id);
            $orderAmount = round((float) ($cart?->cartItemTotal() ?? 0), 2);
            $phpPerPoint = (float) config('rewards.php_per_point', 100);

            return UserReward::query()->create([
                'user_id' => $order->user_id,
                'order_id' => $order->getKey(),
                'points' => (int) floor(($orderAmount + 0.00001) / $phpPerPoint),
                'order_amount' => $orderAmount,
                'php_per_point' => $phpPerPoint,
                'status' => UserReward::STATUS_EARNED,
                'earned_at' => $order->delivered_at ?? now(),
            ]);
        });
    }

    private function reverse(Orders $order): ?UserReward
    {
        $reward = UserReward::query()->where('order_id', $order->getKey())->first();

        if (! $reward || $reward->status === UserReward::STATUS_REVERSED) {
            return $reward;
        }

        $reward->update([
            'status' => UserReward::STATUS_REVERSED,
            'reversed_at' => now(),
            'reversal_reason' => 'Order was cancelled, refunded, or failed.',
        ]);

        return $reward;
    }

    private function isDelivered(Orders $order): bool
    {
        return (int) $order->order_status_id === LibraryStatus::STATUS_DELIVERED
            || $order->delivered_at !== null;
    }

    private function isCancelled(Orders $order): bool
    {
        return (int) $order->order_status_id === LibraryStatus::STATUS_CANCELLED;
    }
}
