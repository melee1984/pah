<?php

namespace App;

use App\Model\Orders\Orders;
use Illuminate\Database\Eloquent\Model;

class StatementAccountItem extends Model
{
    protected $fillable = [
        'order_id',
        'order_number',
        'completed_at',
        'fulfillment_type',
        'order_status',
        'quantity',
        'subtotal_amount',
        'convenience_fee_amount',
        'vat_amount',
        'delivery_fee_amount',
        'discount_amount',
        'total_amount',
        'commission_amount',
        'merchant_net_amount',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'subtotal_amount' => 'decimal:2',
        'convenience_fee_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'delivery_fee_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'merchant_net_amount' => 'decimal:2',
    ];

    public function statement()
    {
        return $this->belongsTo(StatementAccount::class, 'statement_account_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
