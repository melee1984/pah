<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiningPayment extends Model
{
    protected $guarded = [];

    protected $casts = ['amount_cents' => 'integer'];

    public function bill()
    {
        return $this->belongsTo(DiningBill::class, 'dining_bill_id');
    }
}
