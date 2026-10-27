<?php

namespace App\Jobs;

use App\Model\Orders\Orders;
use App\Services\FirebaseMerchantOrderPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendMerchantOrderPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $orderId) {}

    public function handle(FirebaseMerchantOrderPush $push): void
    {
        $order = Orders::query()->with('cart.partnerlocation')->find($this->orderId);
        $cart = $order?->cart;
        $location = $cart?->partnerlocation;
        $token = $location?->device_token;

        if (! $order?->submitted_at || ! $location || (int) $location->partner_id !== (int) $order->partner_id
            || ! is_string($token) || trim($token) === '') {
            return;
        }

        try {
            $push->send(
                $token,
                (string) ($cart->order_no ?: $order->id),
                (int) $order->id,
                (int) $location->id,
            );
        } catch (Throwable $exception) {
            Log::warning('Merchant order push failed.', [
                'order_id' => $order->id,
                'partner_location_id' => $location->id,
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
