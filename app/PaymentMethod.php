<?php

namespace App;

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

    public static function resolveCheckoutId(mixed $paymentMethod): ?int
    {
        if (is_int($paymentMethod) || (is_string($paymentMethod) && ctype_digit($paymentMethod))) {
            $paymentMethod = (int) $paymentMethod;

            return $paymentMethod > 0 ? $paymentMethod : null;
        }

        if (!is_string($paymentMethod)) {
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
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }
}
