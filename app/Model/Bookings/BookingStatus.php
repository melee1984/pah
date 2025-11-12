<?php

namespace App\Model\Bookings;

use Illuminate\Database\Eloquent\Model;

class BookingStatus extends Model
{
    protected $table = 'library_booking_status';
	public $timestamps = false;
}
