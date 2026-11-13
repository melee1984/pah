<?php

namespace App;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QrOrderingCode extends Model
{
    protected $table = 'qr_ordering_codes';

    protected $fillable = [
        'uuid',
        'partner_id',
        'partner_location_id',
        'dining_table_id',
        'label',
        'active',
        'expires_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function partner()
    {
        return $this->belongsTo(Partners::class, 'partner_id');
    }

    public function location()
    {
        return $this->belongsTo(PartnerLocation::class, 'partner_location_id');
    }

    public function diningTable()
    {
        return $this->belongsTo(PartnerLocationTable::class, 'dining_table_id');
    }

    public function scopeUsable(Builder $query): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    public function orderingUrl(): string
    {
        $baseUrl = rtrim((string) config('services.qr_ordering.url'), '?&');
        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl.$separator.http_build_query([
            'qr_uuid' => $this->uuid,
            'partner_id' => $this->partner_id,
            'branch_id' => $this->partner_location_id,
            'table_id' => $this->dining_table_id,
        ]);
    }
}
