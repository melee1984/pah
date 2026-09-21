<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LibraryStatus extends Model
{	
	public const STATUS_ORDER_PLACED = 1;
    public const STATUS_ORDER_ACCEPTED = 2;
    public const STATUS_PROCESSING = 3;
    public const STATUS_READY_FOR_PICKUP = 4;
    public const STATUS_RIDER_ON_THE_WAY_TO_CUSTOMER = 5; // it should have a combination here PICKUP and BOOKING_STATUS_ON_WAY
    public const STATUS_ARRIVAL_AT_CUSTOMER = 6; // it should have a combination here PICKUP and BOOKING_STATUS_ON_WAY
    public const STATUS_DELIVERED = 7;
    public const STATUS_CANCELLED = 8;

    protected $table = 'library_status';
	protected $fillable = array('title');
	public $timestamps = false;
}
