<?php

namespace App\Observers;

use App\Model\Orders\Orders;
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

    public function saved(Orders $order): void
    {
        app(AgentCommissionService::class)->sync($order);
        app(UserRewardService::class)->sync($order);
    }
}
