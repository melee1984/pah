<?php

namespace App\Jobs;

use App\Exceptions\FirebasePushException;
use App\Model\Orders\Orders;
use App\Services\FirebaseMerchantOrderPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendCustomerOrderAcceptedPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $orderId) {}

    public function handle(FirebaseMerchantOrderPush $push): void
    {
        $order = Orders::query()->with(['user', 'cart'])->find($this->orderId);
        $token = $order?->user?->device_token_food;

        if (! $order?->store_accepted_at || ! is_string($token) || trim($token) === '') {
            return;
        }

        try {
            $push->sendCustomerAccepted(
                trim($token),
                (string) ($order->cart?->order_no ?: $order->id),
                (int) $order->id,
            );
        } catch (FirebasePushException $exception) {
            if ($exception->hasInvalidToken()) {
                DB::table('users')
                    ->where('id', $order->user->id)
                    ->where('device_token_food', $token)
                    ->update(['device_token_food' => null]);

                Log::notice('Removed an invalid customer push token.', [
                    'order_id' => $order->id,
                    'user_id' => $order->user->id,
                    'firebase_error_code' => $exception->firebaseErrorCode,
                ]);

                return;
            }

            $this->reportFailure($order->id, $exception);

            throw $exception;
        } catch (Throwable $exception) {
            $this->reportFailure($order->id, $exception);

            throw $exception;
        }
    }

    private function reportFailure(int $orderId, Throwable $exception): void
    {
        Log::warning('Customer order accepted push failed.', [
            'order_id' => $orderId,
            'error' => $exception->getMessage(),
        ]);
    }
}
