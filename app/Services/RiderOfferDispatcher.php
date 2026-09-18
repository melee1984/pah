<?php

namespace App\Services;

use App\Jobs\SendRiderOfferPush;
use App\Model\Orders\Orders;
use App\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RiderOfferDispatcher
{
    public function dispatchOrder(Orders $order): ?string
    {
        if (! $this->tablesAvailable()) {
            return null;
        }

        $delivery = DB::table('rider_api_deliveries')
            ->where('legacy_order_id', $order->getKey())
            ->first();

        if (! $delivery) {
            $order = Orders::query()
                ->with(['cart.address', 'cart.partnerlocation', 'cart.partner', 'user'])
                ->findOrFail($order->getKey());
            $cart = $order->cart;
            $partner = $cart?->partner ?? $order->partner;
            $pickup = $cart?->partnerlocation ?? $partner?->location;
            $dropoff = $cart?->address;
            $reference = (string) Str::uuid();
            $totalCentavos = $cart ? (int) round($cart->cartItemTotal() * 100) : 0;
            $deliveryId = DB::table('rider_api_deliveries')->insertGetId([
                'reference' => $reference,
                'legacy_order_id' => $order->getKey(),
                'current_state' => 'offered',
                'merchant_name' => $partner?->restaurant_name ?? 'Pahatud merchant',
                'pickup_area' => $pickup?->city ?? $partner?->city,
                'pickup_address' => $this->address($pickup?->address_1, $pickup?->address_2, $partner?->address),
                'pickup_latitude' => $pickup?->latitude ?? $partner?->latitude,
                'pickup_longitude' => $pickup?->longtitude ?? $partner?->longtitude,
                'dropoff_area' => $dropoff?->address_2,
                'dropoff_address' => $this->address($dropoff?->address_1, $dropoff?->address_2, $dropoff?->landmark),
                'dropoff_latitude' => $dropoff?->lat ?? $cart?->user_lat,
                'dropoff_longitude' => $dropoff?->long ?? $cart?->user_long,
                'customer_name' => trim((string) ($order->user?->full_name ?? 'Pahatud customer')),
                'customer_mobile' => $dropoff?->mobile ?? $order->user?->mobile,
                'distance_meters' => $this->distanceMeters(
                    $pickup?->latitude ?? $partner?->latitude,
                    $pickup?->longtitude ?? $partner?->longtitude,
                    $dropoff?->lat ?? $cart?->user_lat,
                    $dropoff?->long ?? $cart?->user_long,
                ),
                'eta_seconds' => $this->etaSeconds($cart?->duration),
                'earnings_centavos' => max(0, (int) round(((float) ($cart?->delivery_fee ?? 0)) * 100)),
                'commission_percentage' => config('rider.pahatud_commission_percentage', 20),
                'commission_centavos' => max(0, (int) round(
                    ((float) ($cart?->delivery_fee ?? 0))
                    * 100
                    * ((float) config('rider.pahatud_commission_percentage', 20) / 100)
                )),
                'cod_centavos' => (int) ($cart?->payment_id) === PaymentMethod::CHECKOUT_COD ? $totalCentavos : 0,
                'order_count' => 1,
                'is_batched' => false,
                'pickup_code_hash' => hash('sha256', $this->pickupCode($order->getKey())),
                'customer_code_hash' => hash('sha256', $this->customerCode($order->getKey())),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $delivery = DB::table('rider_api_deliveries')->where('id', $deliveryId)->first();
        }

        if (! $delivery->rider_id && $delivery->current_state === 'offered') {
            foreach ($this->nearbyRiderIds($delivery) as $riderId) {

                \Log::info("Dispatching delivery {$delivery->id} to rider {$riderId}");

                $this->offerDeliveryToRider($delivery->id, $riderId);
            }
        }

        return $delivery->reference;
    }

    public function dispatchPendingForRider(int $riderId): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        $deliveries = DB::table('rider_api_deliveries')
            ->whereNull('rider_id')
            ->where('current_state', 'offered')
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        foreach ($deliveries as $delivery) {
            if (in_array($riderId, $this->nearbyRiderIds($delivery), true)) {
                $this->offerDeliveryToRider((int) $delivery->id, $riderId);
            }
        }
    }

    public function cancelOrder(int $orderId): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }
        $delivery = DB::table('rider_api_deliveries')
            ->where('legacy_order_id', $orderId)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->first();
        if (! $delivery) {
            return;
        }

        DB::transaction(function () use ($delivery) {
            DB::table('rider_api_deliveries')->where('id', $delivery->id)->update([
                'current_state' => 'cancelled',
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('rider_api_offers')
                ->where('delivery_id', $delivery->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'expired',
                    'responded_at' => now(),
                    'updated_at' => now(),
                ]);
            if ($delivery->rider_id) {
                DB::table('rider_api_availability')
                    ->where('rider_id', $delivery->rider_id)
                    ->update(['state' => 'available', 'updated_at' => now()]);
            }
        });
    }

    public function pickupCode(int $orderId): string
    {
        return $this->code($orderId, 'pickup');
    }

    public function customerCode(int $orderId): string
    {
        return $this->code($orderId, 'customer');
    }

    private function offerDeliveryToRider(int $deliveryId, int $riderId): void
    {
        DB::transaction(function () use ($deliveryId, $riderId) {
            // Lock the delivery so concurrent dispatches cannot exceed the rider limit.
            $delivery = DB::table('rider_api_deliveries')->where('id', $deliveryId)->lockForUpdate()->first();
            if (! $delivery || $delivery->rider_id || $delivery->current_state !== 'offered') {
                return;
            }

            $availability = DB::table('rider_api_availability')
                ->where('rider_id', $riderId)
                ->lockForUpdate()
                ->first();
            if (! $availability || $availability->state !== 'available'
                || $this->pendingOfferCount($riderId) >= $this->maxPendingOffersPerRider()) {
                return;
            }

            $hasActiveDelivery = DB::table('rider_api_deliveries')
                ->where('rider_id', $riderId)
                ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
                ->exists();
            if ($hasActiveDelivery || DB::table('rider_api_offers')
                ->where('rider_id', $riderId)->where('delivery_id', $deliveryId)->exists()) {
                return;
            }
            if (! $this->hasPushDevice($riderId)) {
                return;
            }

            // One offer per rider and delivery; the push job sends it to their active devices.
            if (DB::table('rider_api_offers')->where('delivery_id', $deliveryId)->count()
                >= max(1, (int) config('rider.offer_nearby_limit', 10))) {
                return;
            }

            $reference = (string) Str::uuid();
            DB::table('rider_api_offers')->insert([
                'rider_id' => $riderId,
                'delivery_id' => $deliveryId,
                'reference' => $reference,
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if (Schema::hasTable('rider_api_notifications')) {
                DB::table('rider_api_notifications')->insert([
                    'reference' => (string) Str::uuid(),
                    'rider_id' => $riderId,
                    'type' => 'delivery_offer',
                    'title' => 'New delivery offer',
                    'body' => 'A delivery from '.($delivery->merchant_name ?: 'a nearby merchant').' is available.',
                    'deep_link' => '/app/home',
                    'data' => json_encode(['offer_id' => $reference, 'delivery_id' => $delivery->reference], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            SendRiderOfferPush::dispatch($riderId, $reference)->afterCommit();
        });
    }

    private function hasPushDevice(int $riderId): bool
    {
        return DB::table('rider_api_devices')
            ->where('rider_id', $riderId)
            ->whereNull('revoked_at')
            ->whereNotNull('push_token')
            ->where('push_token', '!=', '')
            ->exists();
    }

    private function maxPendingOffersPerRider(): int
    {
        return max(1, (int) config('rider.offer_max_pending_per_rider', 3));
    }

    private function pendingOfferCount(int $riderId): int
    {
        return DB::table('rider_api_offers as pending_offer')
            ->join('rider_api_deliveries as pending_delivery', 'pending_delivery.id', '=', 'pending_offer.delivery_id')
            ->where('pending_offer.rider_id', $riderId)
            ->where('pending_offer.status', 'pending')
            ->where('pending_offer.expires_at', '>', now())
            ->where('pending_delivery.current_state', 'offered')
            ->whereNull('pending_delivery.rider_id')
            ->count();
    }

    /** @return list<int> */
    private function nearbyRiderIds(object $delivery): array
    {
        if (! is_numeric($delivery->pickup_latitude) || ! is_numeric($delivery->pickup_longitude)) {
            return [];
        }

        $maxAgeMinutes = max(1, (int) config('rider.offer_location_max_age_minutes', 5));
        $maxDistanceMeters = max(0, (float) config('rider.offer_max_distance_km', 20)) * 1000;
        $limit = max(1, (int) config('rider.offer_nearby_limit', 10));
        $maxPendingOffers = $this->maxPendingOffersPerRider();

        // Use each rider's most recently recorded fix, not an older nearby fix.
        $riders = DB::table('rider')
            ->join('rider_api_availability', 'rider_api_availability.rider_id', '=', 'rider.id')
            ->join('rider_api_locations as location', 'location.rider_id', '=', 'rider.id')
            ->whereRaw('location.id = (SELECT latest.id FROM rider_api_locations AS latest WHERE latest.rider_id = rider.id ORDER BY latest.recorded_at DESC, latest.id DESC LIMIT 1)')
            ->where('rider.active', true)
            ->where('rider_api_availability.state', 'available')
            ->whereRaw('(SELECT COUNT(*) FROM rider_api_offers AS pending_offer JOIN rider_api_deliveries AS pending_delivery ON pending_delivery.id = pending_offer.delivery_id WHERE pending_offer.rider_id = rider.id AND pending_offer.status = ? AND pending_offer.expires_at > ? AND pending_delivery.current_state = ? AND pending_delivery.rider_id IS NULL) < ?', ['pending', now(), 'offered', $maxPendingOffers])
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rider_api_devices')
                    ->whereColumn('rider_api_devices.rider_id', 'rider.id')
                    ->whereNull('rider_api_devices.revoked_at')
                    ->whereNotNull('rider_api_devices.push_token')
                    ->where('rider_api_devices.push_token', '!=', '');
            })
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('rider_api_deliveries as active_delivery')
                    ->whereColumn('active_delivery.rider_id', 'rider.id')
                    ->whereNotIn('active_delivery.current_state', ['delivered', 'cancelled', 'failed']);
            })
            ->whereBetween('location.recorded_at', [now()->subMinutes($maxAgeMinutes), now()])
            ->select('rider.id', 'location.latitude', 'location.longitude')
            ->get();

        \Log::info('Nearby riders for delivery '.$delivery->id.': '.implode(', ', $riders->pluck('id')->all()));
        
        return $riders
            ->map(function (object $rider) use ($delivery) {
                $rider->distance_meters = $this->distanceMeters(
                    $delivery->pickup_latitude,
                    $delivery->pickup_longitude,
                    $rider->latitude,
                    $rider->longitude,
                );

                return $rider;
            })
            ->filter(fn (object $rider) => $rider->distance_meters !== null
                && $rider->distance_meters <= $maxDistanceMeters)
            ->sort(fn (object $a, object $b) => $a->distance_meters <=> $b->distance_meters ?: $a->id <=> $b->id)
            ->take($limit)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('rider_api_deliveries')
            && Schema::hasTable('rider_api_offers')
            && Schema::hasTable('rider_api_availability')
            && Schema::hasTable('rider_api_devices')
            && Schema::hasTable('rider_api_locations')
            && Schema::hasTable('rider');
    }

    private function code(int $orderId, string $purpose): string
    {
        $digest = hash_hmac('sha256', "{$purpose}:{$orderId}", (string) config('app.key'));

        return str_pad((string) (hexdec(substr($digest, 0, 8)) % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function address(?string ...$parts): ?string
    {
        $parts = array_values(array_filter(array_map(
            fn (?string $part) => $part ? trim($part) : null,
            $parts,
        )));

        return $parts === [] ? null : implode(', ', array_unique($parts));
    }

    private function etaSeconds(mixed $duration): ?int
    {
        if (is_numeric($duration)) {
            return max(0, (int) round((float) $duration));
        }
        if (is_string($duration) && preg_match('/(\d+)/', $duration, $matches)) {
            return (int) $matches[1] * 60;
        }

        return null;
    }

    private function distanceMeters(mixed $fromLat, mixed $fromLng, mixed $toLat, mixed $toLng): ?int
    {
        foreach ([$fromLat, $fromLng, $toLat, $toLng] as $coordinate) {
            if (! is_numeric($coordinate)) {
                return null;
            }
        }
        $fromLat = deg2rad((float) $fromLat);
        $toLat = deg2rad((float) $toLat);
        $latDelta = $toLat - $fromLat;
        $lngDelta = deg2rad((float) $toLng - (float) $fromLng);
        $value = sin($latDelta / 2) ** 2
            + cos($fromLat) * cos($toLat) * sin($lngDelta / 2) ** 2;

        return (int) round(6371000 * 2 * atan2(sqrt($value), sqrt(1 - $value)));
    }
}
