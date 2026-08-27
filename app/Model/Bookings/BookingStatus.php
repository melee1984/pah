<?php

namespace App\Model\Bookings;

use Illuminate\Database\Eloquent\Model;

class BookingStatus extends Model
{
    protected $table = 'library_booking_status';
	public $timestamps = false;
    public const STATUS_BOOKING_PLACED = 1;
    public const STATUS_BOOKING_ACCEPTED = 2;
    public const STATUS_BOOKING_PROCESSING = 3;
    public const STATUS_BOOKING_READY_FOR_PICKUP = 4;
    public const STATUS_BOOKING_RIDER_PICKED_UP = 5;
    public const STATUS_BOOKING_ARRIVAL_AT_CUSTOMER = 6; // Confirm arrival at customer
    public const STATUS_BOOKING_DELIVERED = 7;
    public const STATUS_BOOKING_CANCELLED = 8;

}
