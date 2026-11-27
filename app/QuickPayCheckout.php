<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class QuickPayCheckout extends Model
{
    protected $table = 'quickpay_checkouts';

    protected $guarded = [];

    protected $casts = [
        'request_body' => 'array',
        'guest_snapshot' => 'array',
        'amount_cents' => 'integer',
        'rounding_discount_cents' => 'integer',
        'payment_method_id' => 'integer',
        'livemode' => 'boolean',
    ];

    public const ACTIVE = ['pending', 'confirmed'];

    public static function latestFor(int $billId, bool $lock = false): ?self
    {
        return static::where('dining_bill_id', $billId)->latest('id')->when($lock, fn ($query) => $query->lockForUpdate())->first();
    }

    public static function latestForCart(int $cartId, bool $lock = false): ?self
    {
        return static::where('cart_id', $cartId)->latest('id')->when($lock, fn ($query) => $query->lockForUpdate())->first();
    }

    public static function forDelivery(int $deliveryId): Builder
    {
        return static::where('rider_delivery_id', $deliveryId);
    }

    public static function assertDeliveryUnlocked(int $deliveryId): void
    {
        static::assertNoActiveDeliveryPayment(static::forDelivery($deliveryId));
    }

    public static function assertOrderDeliveryUnlocked(int $orderId): void
    {
        static::assertNoActiveDeliveryPayment(static::whereIn('rider_delivery_id', DB::table('rider_api_deliveries')
            ->where('legacy_order_id', $orderId)->select('id')));
    }

    private static function assertNoActiveDeliveryPayment(Builder $query): void
    {
        abort_if($query->whereIn('status', self::ACTIVE)->lockForUpdate()->first(['id']),
            409, 'A QuickPay payment is in progress. Check its status before continuing.');
    }

    public static function assertCartUnlocked(int $cartId): void
    {
        abort_if(in_array(static::latestForCart($cartId, lock: true)?->status, self::ACTIVE, true),
            409, 'A QuickPay payment is in progress. Check its status before making changes.');
    }

    public static function assertUnlocked(int $billId): void
    {
        abort_if(
            in_array(static::latestFor($billId, lock: true)?->status, self::ACTIVE, true),
            409,
            'A QuickPay payment is in progress. Check its status before making changes.'
        );
    }

    public function payload(bool $revealCode = false): array
    {
        $showCode = $this->status === 'pending' && ($this->monitoring_stopped_at === null || $revealCode);

        return [
            'id' => $this->id,
            'provider' => 'quickpay',
            'monitoring_stopped' => $this->monitoring_stopped_at !== null,
            'livemode' => $this->livemode,
            'method_id' => $this->payment_method_id,
            'channel' => $this->request_body['channel'] ?? null,
            'status' => $this->status === 'confirmed' ? 'pending' : $this->status,
            'amount_cents' => $this->amount_cents,
            'rounding_discount_cents' => $this->rounding_discount_cents,
            'checkout_url' => $showCode ? $this->url : null,
            'qr_payload' => $showCode ? $this->qr_payload : null,
            'qr_image' => $showCode ? $this->qr_image : null,
            'expires_at' => $this->expires_at,
            'rejection_message' => $this->status === 'rejected' ? $this->rejection_message : null,
        ];
    }
}
