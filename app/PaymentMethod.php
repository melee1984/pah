<?php

namespace App;

use App\Contracts\PaymentGateway;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    public const CHECKOUT_CREDIT_CARD = 1;

    public const CHECKOUT_GCASH = 2;

    public const CHECKOUT_COD = 3;

    public const CHECKOUT_PAYMENT_METHODS = [
        'credit_card' => self::CHECKOUT_CREDIT_CARD,
        'gcash' => self::CHECKOUT_GCASH,
        'cod' => self::CHECKOUT_COD,
    ];

    public function isCash(): bool
    {
        $title = strtolower(trim((string) $this->title));

        return (int) $this->id === self::CHECKOUT_COD
            || in_array($title, ['cash', 'cod', 'cash on delivery'], true);
    }

    public const QUICKPAY_CHANNELS = [
        'maya' => 'Maya',
        'gcash' => 'GCash',
        'qrph' => 'QR Ph',
        'banks' => 'Transfer to bank',
        'hosted' => 'Hosted checkout',
    ];

    public function isQuickPay(): bool
    {
        return $this->quickpay_channel !== null;
    }

    public static function quickPayKey(int $partnerId): ?string
    {
        $key = config("services.quickpay.merchant_keys.{$partnerId}");

        if (! is_string($key) || ! preg_match('/^qp_(test|live)_.+$/D', $key)) {
            return null;
        }

        return app()->isProduction() && str_starts_with($key, 'qp_test_') ? null : $key;
    }

    public static function resolveCheckoutId(mixed $paymentMethod): ?int
    {
        if (is_int($paymentMethod) || (is_string($paymentMethod) && ctype_digit($paymentMethod))) {
            $paymentMethod = (int) $paymentMethod;

            return $paymentMethod > 0 ? $paymentMethod : null;
        }

        if (! is_string($paymentMethod)) {
            return null;
        }

        $alias = str_replace(['-', ' '], '_', strtolower(trim($paymentMethod)));

        return self::CHECKOUT_PAYMENT_METHODS[$alias] ?? null;
    }

    protected $table = 'payment_method';

    protected $fillable = [
        'title',
        'description',
        'class',
        'active',
        'available_in_application',
        'available_in_qr',
        'quickpay_channel',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'available_in_application' => 'boolean',
            'available_in_qr' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeForApplication(Builder $query): Builder
    {
        return $query->where('available_in_application', true)->whereNull('quickpay_channel');
    }

    public function scopeForMerchantQr(Builder $query, int $partnerId, bool $supportsQuickPay = true): Builder
    {
        return $query->forQr()->when(
            ! $supportsQuickPay || ! app(PaymentGateway::class)->availableFor($partnerId),
            fn (Builder $methods) => $methods->whereNull('quickpay_channel')
        );
    }

    public function scopeForQr(Builder $query): Builder
    {
        return $query->where('available_in_qr', true);
    }
}
