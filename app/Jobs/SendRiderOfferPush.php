<?php

namespace App\Jobs;

use App\Services\FirebaseRiderPush;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendRiderOfferPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public readonly int $riderId,
        public readonly string $offerReference,
    ) {}

    public function handle(FirebaseRiderPush $push): void
    {
        $offer = DB::table('rider_api_offers')
            ->where('rider_id', $this->riderId)
            ->where('reference', $this->offerReference)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->first();
        if (! $offer) {
            return;
        }

        $delivery = DB::table('rider_api_deliveries')
            ->where('id', $offer->delivery_id)
            ->where('current_state', 'offered')
            ->whereNull('rider_id')
            ->first();
        if (! $delivery) {
            return;
        }

        $notificationsEnabled = DB::table('rider_api_notification_preferences')
            ->where('rider_id', $this->riderId)
            ->value('delivery_offers');
        if ($notificationsEnabled !== null && ! (bool) $notificationsEnabled) {
            return;
        }

        $devices = DB::table('rider_api_devices')
            ->where('rider_id', $this->riderId)
            ->whereNull('revoked_at')
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->get(['id', 'push_token']);

        foreach ($devices as $device) {
            try {
                $push->send(
                    Crypt::decryptString($device->push_token),
                    'New delivery offer',
                    'A delivery from '.($delivery->merchant_name ?: 'a nearby merchant').' is available.',
                    [
                        'type' => 'new_booking',
                        'offer_id' => $this->offerReference,
                        'delivery_id' => (string) $delivery->reference,
                        'booking_id' => (string) ($delivery->legacy_order_id ?? ''),
                    ],
                );
            } catch (Throwable $exception) {
                Log::warning('Rider offer push failed.', [
                    'rider_id' => $this->riderId,
                    'device_id' => $device->id,
                    'offer_id' => $this->offerReference,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
