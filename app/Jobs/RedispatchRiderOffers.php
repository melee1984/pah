<?php

namespace App\Jobs;

use App\Services\RiderOfferDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RedispatchRiderOffers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @param list<int> $deliveryIds */
    public function __construct(public readonly array $deliveryIds) {}

    public function handle(RiderOfferDispatcher $dispatcher): void
    {
        foreach (array_unique($this->deliveryIds) as $deliveryId) {
            $dispatcher->dispatchDelivery((int) $deliveryId);
        }
    }
}
