<?php

namespace App\Model\Bookings;

use Illuminate\Database\Eloquent\Model;

class BookingOrderProcess extends Model
{
    protected $table = 'booking_order_process';
	protected $fillable = array('booking_id', 'status_id', 'user_id');
	public $timestamps = true;
}
