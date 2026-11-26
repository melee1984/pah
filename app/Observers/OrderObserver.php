<?php

namespace App\Observers;

use App\LibraryStatus;
use App\Model\Orders\Orders;
use App\QuickPayCheckout;
use App\Services\AgentCommissionService;
use App\Services\DiningRoom;
use App\Services\UserRewardService;
use Illuminate\Support\Facades\Schema;

class OrderObserver
{
    public function created(Orders $order): void
    {
        if (Schema::hasTable('dining_bill_orders')) {
            app(DiningRoom::class)->attach($order);
        }
    }

    public function updating(Orders $order): void
    {
        $cancellationChanged = $order->isDirty('order_status_id') && in_array(LibraryStatus::STATUS_CANCELLED, [
            (int) $order->getOriginal('order_status_id'),
            (int) $order->order_status_id,
        ], true);

        if ($cancellationChanged && Schema::hasTable('quickpay_checkouts')
            && ($billId = $order->billRound()->value('dining_bill_id'))) {
            QuickPayCheckout::assertUnlocked($billId);
        }
    }

    public function saved(Orders $order): void
    {
        app(AgentCommissionService::class)->sync($order);
        app(UserRewardService::class)->sync($order);
    }
}
