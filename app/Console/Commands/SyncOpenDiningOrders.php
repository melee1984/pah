<?php

namespace App\Console\Commands;

use App\Model\Cart;
use App\Model\Orders\Orders;
use App\Services\DiningRoom;
use Illuminate\Console\Command;

class SyncOpenDiningOrders extends Command
{
    protected $signature = 'dining:sync-open-orders';

    protected $description = 'Attach existing pending dine-in orders to table bills without assuming completed orders are unpaid';

    public function handle(DiningRoom $room): int
    {
        $count = 0;
        Orders::whereNotNull('submitted_at')->where(function ($query) {
            $query->whereIn('order_status_id', [1, 2, 3, 4])
                ->orWhere(fn ($legacy) => $legacy->whereNull('order_status_id')->whereIn('status_id', [1, 2, 3, 4]));
        })
            ->whereHas('cart', fn ($query) => $query->where('fulfillment_type', Cart::FULFILLMENT_DINE_IN)->whereNotNull('dining_table_id'))
            ->whereDoesntHave('billRound')->orderBy('id')->chunkById(100, function ($orders) use ($room, &$count) {
                foreach ($orders as $order) {
                    $room->attach($order);
                    $count++;
                }
            });
        $this->info("Attached {$count} open orders to dining bills.");

        return self::SUCCESS;
    }
}
