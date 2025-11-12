<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;


class DeliveryDistance extends Model
{
    public static function getCoordinateComputation($from_coordinates, $to_coordinates) {

	   	try {
	   		
		$from_latlong = $from_coordinates;
	   	$to_latlong = $to_coordinates;

	   	// echo $from_latlong;
	   	// echo "<br>";
	   	// echo $to_latlong;
	   	// die();
	   	
	   	$distance = "";

	   	$unit = 'K';
		$apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U'; // Demo 
		
		// $distance_data = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$from_latlong.'&destinations='.$to_latlong.'&key='.$apiKey
  //           );
  	
  		$Url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$from_latlong.'&destinations='.$to_latlong.'&key='.$apiKey;

		if (!function_exists('curl_init')){ 
	        die('CURL is not installed!');
	    }
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $Url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $distance_data = curl_exec($ch);
	    curl_close($ch);

		$distance_arr = json_decode($distance_data);

		$destination = $distance_arr->destination_addresses[0];
		$origin = $distance_arr->origin_addresses[0];

	    $distance = $distance_arr->rows[0]->elements[0]->distance->text;
	    $duration = $distance_arr->rows[0]->elements[0]->duration->text;
	   
	    $distance = preg_replace("/[^0-9.]/", "",  $distance);
	    $duration = preg_replace("/[^0-9.]/", "",  $duration);
	   	
	   	// conver to KM
	    $distance=$distance * 1.609344;
	   
		$distance=number_format($distance, 1, '.', '');
		$duration=number_format($duration, 1, '.', '');   

		if (ceil($distance) <=1) {
			$rate = 25;
		}
		else {
			$rate = ((ceil($distance) - 1) * 10);	
			$rate = 25 + $rate;
		}

		// 1 - 25.00 
		// 2 - 35.00 
		// 3 - 45.00 
		// 4 - 55.00 
		// 5 - 65.00 
		// 6 - 75.00 
		// 7 - 85.00 
		// 8 - 95.00 
		// 9 - 105.00 

		$data['status'] = 1;
		$data['distance'] = $distance . "km";
        $data['duration'] = $duration;
        $data['origin'] = $origin;
        $data['destination'] = $destination;

        $data['rate'] = number_format($rate,2);


	   	} catch (Exception $e) {
	   		
	   		$data['status'] = 0;
	   		$data['message'] = "Sorry, please pin your delivery location.";

	   		die(json_encode($data));

	   	}

		return $data;

	}

	public static function getCalendarDelivery($merchant = "") {

        $data = array();
        $dateArray = array();

        $dateStarted = date('Y-m-d');
        $datePicker = array();
 		$interval = DateInterval::createFromDateString('45 min');

        for($i=0;$i<=2;$i++) {
            $dateArray = array();
            if ($dateStarted == date('Y-m-d')) {
            	$currentTime = new DateTime(date('g:i A'));
				$begin = new DateTime("07:45");
        		if ($currentTime > $begin) {
        			$begin = new DateTime(date('g:i a'));
        			$end   = new DateTime("18:00"); // Always end up here
	               
	                $times = new DatePeriod($begin, $interval, $end);
	            	if (!$merchant->is_pre_order) {
		                foreach ($times as $time) {
		                	if ($currentTime > $time->add($interval)->format('g:i A')) {
		                		array_push($dateArray, array('time' => $time->add($interval)->format('g:i A'), 'disabled' => false )); 	
		                	}
		                }
		                if (count($dateArray)>0) {
		                	array_push($datePicker, array('date' => "Today", 'timings' => $dateArray));	
		                }
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

}
