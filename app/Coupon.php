<?php

namespace App;

use App\Model\Cart;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Coupon extends Model
{
    protected $table = 'coupon';

    protected $fillable = [
        'coupon',
        'partner_id',
        'discount_value',
        'discount_percentage',
        'condition',
        'valid_from',
        'valid_until',
        'valid_at',
        'limit',
        'active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'condition' => 'decimal:2',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'valid_at' => 'datetime',
        'limit' => 'integer',
        'active' => 'boolean',
    ];

    public function partner()
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }

    public function scopeAvailable(Builder $query, ?int $partnerId = null): Builder
    {
        return $query
            ->where('active', true)
            ->where(fn (Builder $query) => $query->whereNull('partner_id')
                ->when($partnerId, fn (Builder $query) => $query->orWhere('partner_id', $partnerId)))
            ->where(fn (Builder $query) => $query->whereNull('valid_from')->orWhere('valid_from', '<=', now()))
            ->where(function (Builder $query) {
                $query->where(function (Builder $query) {
                    $query->whereNotNull('valid_until')->where('valid_until', '>=', now());
                })->orWhere(function (Builder $query) {
                    $query->whereNull('valid_until')
                        ->where(fn (Builder $query) => $query->whereNull('valid_at')->orWhere('valid_at', '>=', now()));
                });
            });
    }

    public function scopeWithUsageCount(Builder $query): Builder
    {
        return $query->select('coupon.*')->selectSub(function ($query) {
            $query->from('order')
                ->join('cart', 'cart.id', '=', 'order.cart_id')
                ->whereNotNull('order.submitted_at')
                ->whereColumn('cart.discount_code', 'coupon.coupon')
                ->selectRaw('COUNT(*)');
        }, 'usage_count');
    }

    public function usageCount(): int
    {
        if (array_key_exists('usage_count', $this->attributes)) {
            return (int) $this->attributes['usage_count'];
        }

        return DB::table('order')
            ->join('cart', 'cart.id', '=', 'order.cart_id')
            ->whereNotNull('order.submitted_at')
            ->where('cart.discount_code', $this->coupon)
            ->count();
    }

    public function hasReachedUsageLimit(): bool
    {
        return $this->limit !== null && $this->usageCount() >= $this->limit;
    }

    public static function appliedToCart(Cart $cart): ?self
    {
        if (! $cart->discount_code) {
            return null;
        }

        return static::query()
            ->available($cart->partner_id ? (int) $cart->partner_id : null)
            ->whereRaw('UPPER(coupon) = ?', [strtoupper((string) $cart->discount_code)])
            ->first();
    }

    public function discountFor(float $subtotal): float
    {
        $discount = $this->discount_percentage !== null
            ? $subtotal * ((float) $this->discount_percentage / 100)
            : (float) $this->discount_value;

        return round(min(max($discount, 0), max($subtotal, 0)), 2);
    }
}
