<?php

namespace App;

use App\Model\Cart;
use Illuminate\Database\Eloquent\Model;

class PartnerLocationCheckoutOption extends Model
{
    protected $fillable = [
        'partner_location_id',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public const DETAILS = [
        Cart::FULFILLMENT_DELIVERY => [
            'label' => 'Delivery',
            'description' => 'Delivered to your address',
        ],
        Cart::FULFILLMENT_PICKUP => [
            'label' => 'Pickup',
            'description' => 'Collect from the merchant',
        ],
        Cart::FULFILLMENT_DINE_IN => [
            'label' => 'Dine in',
            'description' => 'Reserve an available table',
        ],
    ];

    public function location()
    {
        return $this->belongsTo(PartnerLocation::class, 'partner_location_id');
    }

    public static function enabledForLocation(int $locationId, string $type): bool
    {
        $query = static::query()->where('partner_location_id', $locationId);

        // Locations created before this feature remain compatible until their
        // merchant saves an explicit configuration.
        if (! $query->exists()) {
            return in_array($type, Cart::FULFILLMENT_TYPES, true);
        }

        return $query->where('type', $type)->where('active', true)->exists();
    }

    public static function availableForLocation(int $locationId): array
    {
        $configured = static::query()
            ->where('partner_location_id', $locationId)
            ->get()
            ->keyBy('type');

        return collect(self::DETAILS)
            ->filter(fn (array $details, string $type) => $configured->isEmpty()
                || (bool) $configured->get($type)?->active)
            ->map(fn (array $details, string $type) => [
                'type' => $type,
                ...$details,
            ])
            ->values()
            ->all();
    }
}
