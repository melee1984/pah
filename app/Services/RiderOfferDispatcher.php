<?php

namespace App\Services;

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

        $riderIds = DB::table('rider')
            ->join('rider_api_availability', 'rider_api_availability.rider_id', '=', 'rider.id')
            ->where('rider.active', true)
            ->where('rider_api_availability.state', 'available')
            ->pluck('rider.id');

        foreach ($riderIds as $riderId) {
            $this->offerDeliveryToRider($delivery->id, (int) $riderId);
        }

        return $delivery->reference;
    }

    public function dispatchPendingForRider(int $riderId): void
    {
        if (! $this->tablesAvailable()) {
            return;
        }

        $deliveryIds = DB::table('rider_api_deliveries')
            ->whereNull('rider_id')
            ->where('current_state', 'offered')
            ->where('created_at', '>=', now()->subDay())
            ->orderBy('created_at')
            ->limit(20)
            ->pluck('id');

        foreach ($deliveryIds as $deliveryId) {
            $this->offerDeliveryToRider((int) $deliveryId, $riderId);
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
        $hasActiveDelivery = DB::table('rider_api_deliveries')
            ->where('rider_id', $riderId)
            ->whereNotIn('current_state', ['delivered', 'cancelled', 'failed'])
            ->exists();
        if ($hasActiveDelivery) {
            return;
        }

        $offer = DB::table('rider_api_offers')
            ->where('rider_id', $riderId)
            ->where('delivery_id', $deliveryId)
            ->first();
        if ($offer && $offer->status !== 'pending') {
            return;
        }

        DB::table('rider_api_offers')->updateOrInsert(
            ['rider_id' => $riderId, 'delivery_id' => $deliveryId],
            [
                'reference' => $offer?->reference ?? (string) Str::uuid(),
                'status' => 'pending',
                'expires_at' => now()->addMinutes(15),
                'responded_at' => null,
                'created_at' => $offer?->created_at ?? now(),
                'updated_at' => now(),
            ],
        );
    }

    private function tablesAvailable(): bool
    {
        return Schema::hasTable('rider_api_deliveries')
            && Schema::hasTable('rider_api_offers')
            && Schema::hasTable('rider_api_availability')
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
