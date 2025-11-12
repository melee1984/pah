<?php

namespace App\Http\Controllers\Map;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Log;

class DistanceController extends Controller
{
    public function index() {

    }

    public function getByPlaceId(Request $request) {

	   	$unit = 'K';
	    $apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U';
	  	//$apiKey = 'AIzaSyCBU3bFyvWzW3R0g0kqQsZxTaay7ImkO14'; // Demo 
	  	$placeId = $request->input('place_id');
	  	// 
	  	// $placeId = 'ChIJGZhNAkcS-TIRF0RdPzBMJHo';

	  	$data = array();

	  	try {
	  		$Url = 'https://maps.googleapis.com/maps/api/place/details/json?place_id='.$placeId.'&key='.$apiKey;
            if (!function_exists('curl_init')){ 
		        die('CURL is not installed!');
		    }
		    $ch = curl_init();
		    curl_setopt($ch, CURLOPT_URL, $Url);
		    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		    $distance_data = curl_exec($ch);
		    curl_close($ch);

			$distance_arr = json_decode($distance_data);

			$data['lat'] = $distance_arr->result->geometry->location->lat;
			$data['long'] = $distance_arr->result->geometry->location->lng;
			$data['name'] = $distance_arr->result->name;

			$data['status'] = 1;

	  	} catch (Exception $e) {
	  			
	  		$data['status'] = 0;
	  	}

		return response()->json($data, 200);

    }

    public function getDistance(Request $request) {

	   	$addressFrom = $request->input('origin');
	   	$addressTo =$request->input('destination');
	   	$unit = 'K';

	    // Google API key
	    // $apiKey = 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk';
	    $apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U';
	    
	    
	    // Change address format
	    $formattedAddrFrom    = str_replace(' ', '+', $addressFrom);
	    $formattedAddrTo     = str_replace(' ', '+', $addressTo);
	    
	    // Geocoding API request with start address
	    $geocodeFrom = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrFrom.'&sensor=false&key='.$apiKey);

	    $outputFrom = json_decode($geocodeFrom);
	    if(!empty($outputFrom->error_message)){
	        return $outputFrom->error_message;
	    }
	    
	    // Geocoding API request with end address
	    $geocodeTo = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?address='.$formattedAddrTo.'&sensor=false&key='.$apiKey);
	    $outputTo = json_decode($geocodeTo);
	    if(!empty($outputTo->error_message)){
	        return $outputTo->error_message;
	    }
	    
	    // Get latitude and longitude from the geodata
	    $latitudeFrom    = $outputFrom->results[0]->geometry->location->lat;
	    $longitudeFrom    = $outputFrom->results[0]->geometry->location->lng;
	    $latitudeTo        = $outputTo->results[0]->geometry->location->lat;
	    $longitudeTo    = $outputTo->results[0]->geometry->location->lng;
	    
	    // Calculate distance between latitude and longitude
	    $theta    = $longitudeFrom - $longitudeTo;
	    $dist    = sin(deg2rad($latitudeFrom)) * sin(deg2rad($latitudeTo)) +  cos(deg2rad($latitudeFrom)) * cos(deg2rad($latitudeTo)) * cos(deg2rad($theta));
	    $dist    = acos($dist);
	    $dist    = rad2deg($dist);
	    $miles    = $dist * 60 * 1.1515;
	        
	   

	    // Convert unit and return distance
	    $unit = strtoupper($unit);
	    if($unit == "K"){
	        $distance = $miles * 1.609344;
	    }elseif($unit == "M"){
	        $distance =round($miles * 1609.344, 2).' meters';
	    }else{
	        $distance = round($miles, 2).' miles';
	    }

	    if (ceil($distance) <=3) {
			$rate = 55;
		}
		else {
			$rate = ((ceil($distance) - 3) * 10);	
			$rate = 55 + $rate;
		}

	    $data['status'] = 1;
		$data['distance'] = $distance . "km";
        $data['duration'] = "";
        $data['origin'] = $addressFrom;
        $data['destination'] = $addressTo;

        $data['rate'] = number_format($rate,2);

		return response()->json($data, 200);

	}

	public function getByLocation(Request $request) {

		$addressFrom = $request->input('origin');
	   	$addressTo =$request->input('destination');

	   	$formattedAddrFrom    = str_replace(' ', '+', $addressFrom);
	    $formattedAddrTo     = str_replace(' ', '+', $addressTo);

	   	$unit = 'K';
			    // $apiKey = 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk';
	    $apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U';


	  	try {
	  		
	  		$Url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$formattedAddrFrom.'&destinations='.$formattedAddrTo.'&key='.$apiKey;
	  		// $distance_data = file_get_contents(); // DOes not work using file get content adjusted to CURL YES!
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

			if (ceil($distance) <=3) {
				$rate = 55;
			}
			else {
				$rate = ((ceil($distance) - 3) * 10);	
				$rate = 55 + $rate;
			}

			$data['status'] = 1;

	  	} catch (Exception $e) {
	  			
	  		$data['status'] = 0;
	  	}

		
		$data['distance'] = $distance . "km";
        $data['duration'] = $duration;
        $data['origin'] = $origin;
        $data['destination'] = $destination;

        $data['rate'] = number_format($rate,2);

		return response()->json($data, 200);

	}

	public function getKilometerByCoodinates(Request $request) {

		$from_latlong = $request->input('pick_lat') .",". $request->input('pick_lng');
	   	$to_latlong = $request->input('del_lat') .",". $request->input('del_lng');

	   	$unit = 'K';
		// $apiKey = 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk';
	    $apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U';

		$distance_data = file_get_contents('https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$from_latlong.'&destinations='.$to_latlong.'&key='.$apiKey
            );

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

		if (ceil($distance) <=3) {
			$rate = 55;
		}
		else {
			$rate = ((ceil($distance) - 3) * 10);	
			$rate = 55 + $rate;
		}

		$data['status'] = 1;
		$data['distance'] = $distance . "km";
        $data['duration'] = $duration;
        $data['origin'] = $origin;
        $data['destination'] = $destination;

        $data['rate'] = number_format($rate,2);

		return response()->json($data, 200);

	}

	public function getTestKM() {

	   	$from_latlong = "7.055356,125.577460";
	   	$to_latlong = "7.055026,125.601940";

	   	$unit = 'K';
		// $apiKey = 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk';
	    $apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U';
		// $distance_data = file_get_contents();
		$Url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$from_latlong.'&destinations='.$to_latlong.'&key='.$apiKey;
            
		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $Url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	    $distance_data = curl_exec($ch);
	    curl_close($ch);

		$distance_arr = json_decode($distance_data);

		dd($distance_arr);

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

		if (ceil($distance) <=3) {
			$rate = 55;
		}
		else {
			$rate = ((ceil($distance) - 3) * 10);	
			$rate = 55 + $rate;
		}

		$data['status'] = 1;
		$data['distance'] = $distance . "km";
        $data['duration'] = $duration;
        $data['origin'] = $origin;
        $data['destination'] = $destination;

        $data['rate'] = number_format($rate,2);

		return response()->json($data, 200);

	}

	/**
	 * // $lat_a = '7.055356';
		// $long_a = '125.577460';

		// $lat_b = '7.055026';
		// $long_b = '125.601940';

		// echo calculateDistanceBetweenTwoPoints($lat_a,$long_a,$lat_b,$long_b,'KM',true,5);
	*
	* @param  string  $latitudeOne   [description]
	* @param  string  $longitudeOne  [description]
	* @param  string  $latitudeTwo   [description]
	* @param  string  $longitudeTwo  [description]
	* @param  string  $distanceUnit  [description]
	* @param  boolean $round         [description]
	* @param  string  $decimalPoints [description]
	* @return [type]                 [description]
	*/

	public function calculateDistanceBetweenTwoPoints(
          $latitudeOne='', 
          $longitudeOne='', 
          $latitudeTwo='', 
          $longitudeTwo='',
          $distanceUnit ='',
          $round=false,
          $decimalPoints='')
    {
        if (empty($decimalPoints)) 
        {
            $decimalPoints = '3';
        }
        if (empty($distanceUnit)) {
            $distanceUnit = 'KM';
        }

        $distanceUnit = strtolower($distanceUnit);
        $pointDifference = $longitudeOne - $longitudeTwo;
        $toSin = (sin(deg2rad($latitudeOne)) * sin(deg2rad($latitudeTwo))) + (cos(deg2rad($latitudeOne)) * cos(deg2rad($latitudeTwo)) * cos(deg2rad($pointDifference)));
        $toAcos = acos($toSin);
        $toRad2Deg = rad2deg($toAcos);

        $toMiles  =  $toRad2Deg * 60 * 1.1515;
        $toKilometers = $toMiles * 1.609344;
        $toNauticalMiles = $toMiles * 0.8684;
        $toMeters = $toKilometers * 1000;
        $toFeets = $toMiles * 5280;
        $toYards = $toFeets / 3;

	      switch (strtoupper($distanceUnit)) 
	      {
	          case 'ML'://miles
	                 $toMiles  = ($round == true ? round($toMiles) : round($toMiles, $decimalPoints));
	                 return $toMiles;
	              break;
	          case 'KM'://Kilometers
	                $toKilometers  = ($round == true ? ceil($toKilometers) : round($toKilometers, $decimalPoints));
	                return $toKilometers;
	              break;
	          case 'MT'://Meters
	                $toMeters  = ($round == true ? round($toMeters) : round($toMeters, $decimalPoints));
	                return $toMeters;
	              break;
	          case 'FT'://feets
	                $toFeets  = ($round == true ? round($toFeets) : round($toFeets, $decimalPoints));
	                return $toFeets;
	              break;
	          case 'YD'://yards
	                $toYards  = ($round == true ? round($toYards) : round($toYards, $decimalPoints));
	                return $toYards;
	              break;
	          case 'NM'://Nautical miles
	                $toNauticalMiles  = ($round == true ? round($toNauticalMiles) : round($toNauticalMiles, $decimalPoints));
	                return $toNauticalMiles;
	              break;
	      }

    }


//     select 

// id, 
// restaurant_name,
// partners.longtitude, 
// partners.latitude,
// ( select ST_Distance_Sphere(
//     point(partners.longtitude, partners.latitude),
//     point(125.545621,7.053074)) * 0.001 / 1000)  as meter 
    
//   from p1.partners 
//  order by meter asc
 
}

