<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

use Carbon\Carbon;
use DateTime;
use DateInterval;
use DatePeriod;

class DeliveryDistance extends Model
{
	public static function getCoordinateComputation($from_coordinates, $to_coordinates)
	{
		try {

			\Log::info('Starting getCoordinateComputation', [
				'from' => $from_coordinates,
				'to' => $to_coordinates
			]);
			
			$from_latlong = $from_coordinates;
			$to_latlong = $to_coordinates;

			$apiKey = config('services.google.maps_key'); // store in .env: GOOGLE_MAPS_KEY

			$url = 'https://maps.googleapis.com/maps/api/distancematrix/json';

			// Use Laravel HTTP client instead of cURL
			$response = Http::get($url, [
				'units' => 'imperial',
				'origins' => $from_latlong,
				'destinations' => $to_latlong,
				'key' => $apiKey,
			]);

			if (!$response->ok()) {
				throw new \Exception('Failed to fetch distance data.');
			}

			$distance_arr = $response->json();
			\Log::info('Distance Matrix API response', $distance_arr);

			// Check if API returned results
			if (empty($distance_arr['rows'][0]['elements'][0]['distance']['text'])) {
				throw new \Exception('Distance data not found.');
			}

			$origin = $distance_arr['origin_addresses'][0] ?? '';
			$destination = $distance_arr['destination_addresses'][0] ?? '';

			$distance = $distance_arr['rows'][0]['elements'][0]['distance']['text'] ?? '0';
			$duration = $distance_arr['rows'][0]['elements'][0]['duration']['text'] ?? '0';

			// Extract numbers
			$distance = floatval(preg_replace("/[^0-9.]/", "", $distance));
			$duration = floatval(preg_replace("/[^0-9.]/", "", $duration));

			// Convert miles to km
			$distance = $distance * 1.609344;

			$distance = number_format($distance, 1, '.', '');
			$duration = number_format($duration, 1, '.', '');

			// Compute rate
			if (ceil($distance) <= 1) {
				\Log::info('Distance is within 1 km, applying base rate.');
				$rate = config('services.delivery.rate');;
			} else {
				$rate = config('services.delivery.rate') + ((ceil($distance) - 1) *  config('services.delivery.additional_km_rate'));
			}

			$data = [
				'status' => 1,
				'distance' => $distance . 'km',
				'duration' => $duration,
				'origin' => $origin,
				'destination' => $destination,
				'rate' => number_format($rate, 2),
			];

			\Log::info('Computed distance and rate', $data);
			return $data;

		} catch (\Exception $e) {
			return [
				'status' => 0,
				'message' => $e->getMessage() ?: "Sorry, please pin your delivery location.",
			];
		}
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
