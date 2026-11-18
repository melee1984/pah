<?php

namespace App;

use App\Model\Orders\Orders;
use Illuminate\Database\Eloquent\Model;

class DiningBillOrder extends Model
{
    protected $guarded = [];

    protected $casts = ['amounts' => 'array', 'items' => 'array'];

    public function bill()
    {
        return $this->belongsTo(DiningBill::class, 'dining_bill_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class);
    }
}
