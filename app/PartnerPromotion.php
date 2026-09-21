<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PartnerPromotion extends Model
{
    protected $fillable = [
        'partner_id',
        'name',
        'subtitle',
        'description',
        'cta_label',
        'link_url',
        'image_path',
        'active',
        'sort_order',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function scopeVisible(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $query) {
                $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query) {
                $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function partner()
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }

    public function getImageUrlAttribute(): string
    {
        return asset($this->image_path);
    }
}
