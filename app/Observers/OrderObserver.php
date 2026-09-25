<?php

namespace App\Observers;

use App\Model\Orders\Orders;
use App\Services\AgentCommissionService;
use App\Services\UserRewardService;

class OrderObserver
{
    public function saved(Orders $order): void
    {
        app(AgentCommissionService::class)->sync($order);
        app(UserRewardService::class)->sync($order);
    }
}
