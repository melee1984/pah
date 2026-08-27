<?php

namespace App\Model\Rider;

use App\Model\Orders\Orders;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiderDeclineOrder extends Model
{
    protected $table = 'rider_decline_order';

    protected $fillable = [
        'rider_id',
        'order_id',
    ];

    public function rider(): BelongsTo
    {
        return $this->belongsTo(Rider::class, 'rider_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
