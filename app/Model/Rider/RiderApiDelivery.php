<?php

namespace App\Model\Rider;

use App\Model\Bookings\Bookings;
use App\Model\Orders\Orders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderApiDelivery extends Model
{
    protected $table = 'rider_api_deliveries';

    protected $fillable = [
        'reference',
        'rider_id',
        'legacy_order_id',
        'legacy_booking_id',
        'current_state',
        'merchant_name',
        'pickup_area',
        'pickup_address',
        'pickup_latitude',
        'pickup_longitude',
        'dropoff_area',
        'dropoff_address',
        'dropoff_latitude',
        'dropoff_longitude',
        'customer_name',
        'customer_mobile',
        'distance_meters',
        'eta_seconds',
        'earnings_centavos',
        'cod_centavos',
        'order_count',
        'is_batched',
        'pickup_code_hash',
        'customer_code_hash',
        'accepted_at',
        'completed_at',
    ];

    protected $hidden = [
        'pickup_code_hash',
        'customer_code_hash',
    ];

    protected function casts(): array
    {
        return [
            'pickup_latitude' => 'decimal:7',
            'pickup_longitude' => 'decimal:7',
            'dropoff_latitude' => 'decimal:7',
            'dropoff_longitude' => 'decimal:7',
            'distance_meters' => 'integer',
            'eta_seconds' => 'integer',
            'earnings_centavos' => 'integer',
            'cod_centavos' => 'integer',
            'order_count' => 'integer',
            'is_batched' => 'boolean',
            'accepted_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'reference';
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'rider_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class, 'legacy_order_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Bookings::class, 'legacy_booking_id');
    }
}
