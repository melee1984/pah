<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class DiningBill extends Model
{
    protected $guarded = [];

    protected $casts = ['totals' => 'array', 'receipt' => 'array', 'closed_at' => 'datetime'];

    public function rounds()
    {
        return $this->hasMany(DiningBillOrder::class)->orderBy('round');
    }

    public function payment()
    {
        return $this->hasOne(DiningPayment::class);
    }
}
