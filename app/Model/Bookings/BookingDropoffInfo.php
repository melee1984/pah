<?php

namespace App\Model\Bookings;

use Illuminate\Database\Eloquent\Model;

class BookingDropoffInfo extends Model
{
    protected $table = 'booking_drop_info';
	protected $fillable = array('latitude', 'longtitude', 'map_address', 'address', 'name', 'mobile', 'landmark', 'user_id');
	public $timestamps = true;
}
