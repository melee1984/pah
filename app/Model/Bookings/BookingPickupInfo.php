<?php

namespace App\Model\Bookings;

use Illuminate\Database\Eloquent\Model;

class BookingPickupInfo extends Model
{
    protected $table = 'booking_pickup_info';
	protected $fillable = array('latitude', 'longtitude', 'map_address', 'address', 'name', 'mobile', 'landmark', 'user_id');
	public $timestamps = true;
}
