<?php

namespace App\Http\Controllers\Api\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

class RequestBooking extends Controller
{
	public function getAvailableSchedule() {

        $data = array();
        $dateArray = array();

        $dateStarted = date('Y-m-d');
        $datePicker = array();

        for($i=0;$i<=10;$i++) {
            
            $dateArray = array();

            if ($dateStarted == date('Y-m-d')) {

                $begin = new DateTime(date('h:i a', time()));
                $end   = new DateTime("18:00"); // Always end up here

                $interval = DateInterval::createFromDateString('45 min');
                $times = new DatePeriod($begin, $interval, $end);

                foreach ($times as $time) {
                    array_push($dateArray, array('time' => $time->add($interval)->format('g:i A'), 'disabled' => false )); 
                }
                array_push($datePicker, array('date' => "Today", 'timings' => $dateArray));
                $dateStarted = date('Y-m-d', strtotime($dateStarted. ' + 1 days'));    
            }
            else {
                $begin = new DateTime("07:15");
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

       $data['schedule'] = $datePicker;
        $data['errandType'] = array(array('id'=>'1', 'disabled' => false, 'title' => 'Pickup and Delivery'), 
                                    array('id'=>'2', 'disabled' => true, 'title' => 'Papalit'),
                                    array('id'=>'3', 'disabled' => true, 'title' => 'Bills Payment'));

        return $data;

    }

    public function insertRecord(Request $request) {

        echo "TEST";
        die();

    }
    


}
