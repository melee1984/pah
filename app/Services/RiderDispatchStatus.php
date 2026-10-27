<?php

namespace App\Services;

use App\LibraryStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RiderDispatchStatus
{
    public function forOrders(Collection $orders): Collection
    {
        if ($orders->isEmpty()) {
            return collect();
        }

        $deliveries = DB::table('rider_api_deliveries')
            ->whereIn('legacy_order_id', $orders->pluck('id'))
            ->get(['id', 'legacy_order_id', 'rider_id', 'current_state'])
            ->keyBy('legacy_order_id');

        $offers = DB::table('rider_api_offers')
            ->whereIn('delivery_id', $deliveries->pluck('id'))
            ->get(['delivery_id', 'status', 'expires_at'])
            ->groupBy('delivery_id');

        return $orders->mapWithKeys(function ($order) use ($deliveries, $offers) {
            $delivery = $deliveries->get($order->id);
            $deliveryOffers = $delivery ? $offers->get($delivery->id, collect()) : collect();
            $activeOffers = $deliveryOffers->filter(fn (object $offer) => $offer->status === 'pending'
                && now()->lessThan($offer->expires_at));
            $expiredOffers = $deliveryOffers->filter(fn (object $offer) => $offer->status === 'expired'
                || ($offer->status === 'pending' && now()->greaterThanOrEqualTo($offer->expires_at)));
            $assigned = (bool) ($order->rider_id ?? null)
                || (bool) ($order->accepted_by_rider_id ?? null)
                || (bool) ($delivery?->rider_id);

            [$status, $label] = $this->statusAndLabel(
                $order,
                $delivery,
                $assigned,
                $activeOffers->isNotEmpty(),
                $expiredOffers->isNotEmpty(),
                $deliveryOffers->isNotEmpty(),
            );

            return [$order->id => [
                'assigned' => $assigned,
                'status' => $status,
                'label' => $label,
                'pending_offers' => $activeOffers->count(),
                'expired_offers' => $expiredOffers->count(),
            ]];
        });
    }

    private function statusAndLabel(
        object $order,
        ?object $delivery,
        bool $assigned,
        bool $hasActiveOffer,
        bool $hasExpiredOffer,
        bool $hasAnyOffer,
    ): array {
        if (! $order->store_accepted_at) {
            return ['awaiting_merchant_acceptance', 'Awaiting merchant acceptance'];
        }

        if ($assigned) {
            if ((int) $order->order_status_id === LibraryStatus::STATUS_READY_FOR_PICKUP
                && in_array($delivery?->current_state, ['accepted', 'arrived_at_pickup'], true)) {
                return ['awaiting_pickup', 'Awaiting rider pickup'];
            }

            return match ($delivery?->current_state) {
                'arrived_at_pickup' => ['rider_at_merchant', 'Rider at merchant'],
                'picked_up', 'going_to_customer', 'arrived_at_customer' => ['in_delivery', 'On the way to customer'],
                'delivered' => ['delivered', 'Delivered'],
                default => ['assigned', 'Rider assigned'],
            };
        }

        if ($delivery && $delivery->current_state !== 'offered') {
            return [$delivery->current_state, str($delivery->current_state)->replace('_', ' ')->title()->toString()];
        }

        if ($hasActiveOffer) {
            return ['offer_active', 'Awaiting rider response'];
        }

        if ($hasExpiredOffer) {
            return ['offer_expired', 'Rider offer expired'];
        }

        if ($hasAnyOffer) {
            return ['no_active_offers', 'No rider accepted'];
        }

        return ['awaiting_rider', 'No rider available yet'];
    }
}
