<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

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

    public function discountFor(float $subtotal): float
    {
        $discount = $this->discount_percentage !== null
            ? $subtotal * ((float) $this->discount_percentage / 100)
            : (float) $this->discount_value;

        return round(min(max($discount, 0), max($subtotal, 0)), 2);
    }
}
