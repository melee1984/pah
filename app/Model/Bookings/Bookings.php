<?php

namespace App\Model\Bookings;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;
use DB;
use App\Model\Bookings\BookingStatus;

class Bookings extends Model
{
    protected $table = 'bookings';
	protected $fillable = array('user_id', 'status_id','booking_date', 'booking_time', 'rider_id', 'errand_type_id', 'delivery_rate', 'discount', 'coupon', 'duration', 'sender_will_pay_df', 'cod', 'item_info', 'instruction', 'distance', 'map_info', 'job_order', 'active', 'pickup_id', 'dropoff_id', 'payment_type');
	public $timestamps = true;

	public function dropoff() 
    {
         return $this->hasOne('App\Model\Bookings\BookingDropoffInfo','id', 'dropoff_id');
    }

    public function pickup() 
    {	
    	return $this->hasOne('App\Model\Bookings\BookingPickupInfo','id', 'pickup_id');
    }

    public function user() 
    {
         return $this->hasOne('App\User', 'id', 'user_id');
    }

    public function status() {
        return $this->hasOne('App\Model\Bookings\BookingStatus','id', 'status_id');   
    }

    public function rider() 
    {
         return $this->hasOne('App\Model\Riders','id', 'rider_id');
    }
	 /**
     * [getTotalCartForToday description]
     * @return [type] [description]
     */
    public static function generateOrderNo() {
        
        $order = Bookings::count() + 1;
        $length = 4;
        return $orderNo = substr(str_repeat(0, $length)."82".$order, - $length);
    }

    public static function getBookingCalendar() {

        $data = array();
        $dateArray = array();

        $dateStarted = date('Y-m-d');
        $datePicker = array();
 		$interval = DateInterval::createFromDateString('45 min');

        for($i=0;$i<=5;$i++) {
            $dateArray = array();
            if ($dateStarted == date('Y-m-d')) {
            	$currentTime = new DateTime(date('g:i A'));
				$begin = new DateTime("07:45");
        		if ($currentTime > $begin) {
        			$begin = new DateTime(date('g:i a'));
        			$end   = new DateTime("18:00"); // Always end up here
	                $times = new DatePeriod($begin, $interval, $end);
	            	
		                foreach ($times as $time) {
		                	if ($currentTime > $time->add($interval)->format('g:i A')) {
		                		array_push($dateArray, array('time' => $time->add($interval)->format('g:i A'), 'disabled' => false )); 	
		                	}
		                }
		                if (count($dateArray)>0) {
		                	array_push($datePicker, array('date' => "Today", 'timings' => $dateArray));	
		                }
	            	
        		}
				$dateStarted = date('Y-m-d', strtotime($dateStarted. ' + 1 days')); 
            }
            else {

                $begin = new DateTime("07:45");
                $end   = new DateTime("18:00"); // Always end up here
                $times = new DatePeriod($begin, $interval, $end);
                $ctr = 0;
                foreach ($times as $time) {
                    if ($ctr == 0) {
                        $x = DateInterval::createFromDateString('0 min');
                        array_push($dateArray, array('time' => $time->add($interval)->format('g:i A'), 'disabled' => false )); 
                        $ctr = 1;
                    }
                    else {
                        $interval = DateInterval::createFromDateString('45 min');
                        array_push($dateArray, array('time' => $time->add($interval)->format('g:i A'), 'disabled' => false )); 
                    }
                }
                array_push($datePicker, array('date' => date('F d, Y', strtotime($dateStarted)), 'timings' => $dateArray));
                $dateStarted = date('Y-m-d', strtotime($dateStarted. ' + 1 days'));    

            }
        }
		return $datePicker;
    }

    public function getActionLogs() {

    if ($this->status_id == 7) {

         $orders = BookingStatus::select('library_booking_status.*' , 
                    DB::raw('(select 
                                count(booking_order_process.id) 
                            from 
                                booking_order_process 
                            where 
                                booking_order_process.status_id = library_booking_status.id and booking_order_process.booking_id = '.$this->id.' limit 0,1)  as st'),

                     DB::raw('(select 
                                booking_order_process.created_at
                            from 
                                booking_order_process 
                            where 
                                booking_order_process.status_id = library_booking_status.id and booking_order_process.booking_id = '.$this->id.' limit 0,1)  as datecreated'))
                    ->whereRaw('library_booking_status.id in (1, 7)')
                    ->get();

    }
    else {

         $orders = BookingStatus::select('library_booking_status.*' , 
                    DB::raw('(select 
                                count(booking_order_process.id) 
                            from 
                                booking_order_process 
                            where 
                                booking_order_process.status_id = library_booking_status.id and booking_order_process.booking_id = '.$this->id.' limit 0,1)  as st'),
                    DB::raw('(select 
                                booking_order_process.created_at
                            from 
                                booking_order_process 
                            where 
                                booking_order_process.status_id = library_booking_status.id and booking_order_process.booking_id = '.$this->id.' limit 0,1)  as datecreated'))
                    ->whereRaw('library_booking_status.id != 7')
                    ->get();
    }
         foreach($orders as $order) {
            if ($order->datecreated !="") {
                $order->time_accepted = date('G:ia',  strtotime($order->datecreated));    
            }
         }
        return $orders;        
    }    

}
