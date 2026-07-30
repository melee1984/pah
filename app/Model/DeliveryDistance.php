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
			[$fromLatitude, $fromLongitude] = self::parseCoordinates($from_coordinates);
			[$toLatitude, $toLongitude] = self::parseCoordinates($to_coordinates);
		} catch (\InvalidArgumentException $exception) {
			return [
				'status' => 0,
				'message' => $exception->getMessage(),
			];
		}

		$originCoordinates = $fromLatitude . ',' . $fromLongitude;
		$destinationCoordinates = $toLatitude . ',' . $toLongitude;
		$apiKey = config('services.google.maps_key');

		\Log::info('Starting getCoordinateComputation', [
			'from' => $originCoordinates,
			'to' => $destinationCoordinates,
		]);

		if (!empty($apiKey)) {
			try {
				$response = Http::timeout(10)->get(
					'https://maps.googleapis.com/maps/api/distancematrix/json',
					[
						'units' => 'metric',
						'origins' => $originCoordinates,
						'destinations' => $destinationCoordinates,
						'key' => $apiKey,
					]
				);

				$responseData = $response->json();
				$elementStatus = data_get($responseData, 'rows.0.elements.0.status');

				\Log::info('Distance Matrix API response', [
					'http_status' => $response->status(),
					'status' => data_get($responseData, 'status'),
					'element_status' => $elementStatus,
				]);

				if (
					!$response->successful()
					|| data_get($responseData, 'status') !== 'OK'
					|| $elementStatus !== 'OK'
				) {
					throw new \RuntimeException(
						data_get($responseData, 'error_message', 'Distance route data was not available.')
					);
				}

				$distanceMeters = data_get($responseData, 'rows.0.elements.0.distance.value');
				$durationSeconds = data_get($responseData, 'rows.0.elements.0.duration.value');

				if (!is_numeric($distanceMeters) || !is_numeric($durationSeconds)) {
					throw new \RuntimeException('Distance route data was incomplete.');
				}

				return self::buildCoordinateComputationResult(
					(float) $distanceMeters / 1000,
					(int) ceil((float) $durationSeconds / 60),
					(string) data_get($responseData, 'origin_addresses.0', $originCoordinates),
					(string) data_get($responseData, 'destination_addresses.0', $destinationCoordinates)
				);
			} catch (\Throwable $exception) {
				\Log::warning('Distance Matrix API failed; using coordinate distance fallback.', [
					'message' => $exception->getMessage(),
					'from' => $originCoordinates,
					'to' => $destinationCoordinates,
				]);
			}
		}

		$distanceKilometers = self::getStraightLineDistanceKilometers(
			$fromLatitude,
			$fromLongitude,
			$toLatitude,
			$toLongitude
		);
		$estimatedSpeedKilometersPerHour = max(
			1,
			(float) config('services.delivery.fast_speed_kph', 30)
		);
		$durationMinutes = (int) ceil(
			($distanceKilometers / $estimatedSpeedKilometersPerHour) * 60
		);

		return self::buildCoordinateComputationResult(
			$distanceKilometers,
			$durationMinutes,
			$originCoordinates,
			$destinationCoordinates
		);
	}

	public static function getRateFromDistanceKilometers($distanceKilometers): float
	{
		if (is_string($distanceKilometers)) {
			$distanceKilometers = str_replace(',', '', trim($distanceKilometers));
		}

		if (!is_numeric($distanceKilometers) || (float) $distanceKilometers < 0) {
			return 0.0;
		}

		$distanceKilometers = (float) $distanceKilometers + 4; // add 4km because we are using a straight line distance and not the actual road distance. This is to account for the difference between straight line and actual road distance. this will match from the google map matrics. later maybe we can use google map matrics to get the actual road distance but for now we will use this method. to make sure that we are getting the precise delivery information. 
		$baseRate = (float) config('services.delivery.rate', 0);
		$additionalKilometerRate = (float) config('services.delivery.additional_km_rate', 0);
		$billableKilometers = max(1, (int) ceil($distanceKilometers));

		return round(
			$baseRate + (($billableKilometers - 1) * $additionalKilometerRate),
			2
		);
	}

	public static function getEstimatedDeliveryTimeFromDistanceKilometers($distanceKilometers): array
	{
		$minimumPreparationMinutes = max(
			0,
			(int) config('services.delivery.preparation_min_minutes', 30)
		);
		$maximumPreparationMinutes = max(
			$minimumPreparationMinutes,
			(int) config('services.delivery.preparation_max_minutes', 45)
		);

		if (is_string($distanceKilometers)) {
			$distanceKilometers = str_replace(',', '', trim($distanceKilometers));
		}

		if (!is_numeric($distanceKilometers) || (float) $distanceKilometers < 0) {
			return [
				'min_minutes' => $minimumPreparationMinutes,
				'max_minutes' => $maximumPreparationMinutes,
			];
		}

		$distanceKilometers = (float) $distanceKilometers;
		$fastSpeedKilometersPerHour = max(
			1,
			(float) config('services.delivery.fast_speed_kph', 30)
		);
		$slowSpeedKilometersPerHour = max(
			1,
			(float) config('services.delivery.slow_speed_kph', 20)
		);

		$minimumTravelMinutes = (int) ceil(
			($distanceKilometers / $fastSpeedKilometersPerHour) * 60
		);
		$maximumTravelMinutes = (int) ceil(
			($distanceKilometers / $slowSpeedKilometersPerHour) * 60
		);

		return [
			'min_minutes' => $minimumPreparationMinutes + $minimumTravelMinutes,
			'max_minutes' => $maximumPreparationMinutes + $maximumTravelMinutes,
		];
	}

	private static function parseCoordinates($coordinates): array
	{
		if (is_string($coordinates)) {
			$coordinates = array_map('trim', explode(',', $coordinates));
		}

		if (!is_array($coordinates)) {
			throw new \InvalidArgumentException(
				'The delivery coordinates are invalid.'
			);
		}

		$latitude = $coordinates['latitude']
			?? $coordinates['lat']
			?? $coordinates[0]
			?? null;

		$longitude = $coordinates['longitude']
			?? $coordinates['longtitude']
			?? $coordinates['lng']
			?? $coordinates['long']
			?? $coordinates[1]
			?? null;

		if (
			!is_numeric($latitude)
			|| !is_numeric($longitude)
			|| (float) $latitude < -90
			|| (float) $latitude > 90
			|| (float) $longitude < -180
			|| (float) $longitude > 180
		) {
			throw new \InvalidArgumentException(
				'The delivery coordinates are invalid.'
			);
		}

		return [
			(float) $latitude,
			(float) $longitude,
		];
	}

	private static function getStraightLineDistanceKilometers(
		float $fromLatitude,
		float $fromLongitude,
		float $toLatitude,
		float $toLongitude
	): float {
		$earthRadiusKilometers = 6371;
		$latitudeDifference = deg2rad($toLatitude - $fromLatitude);
		$longitudeDifference = deg2rad($toLongitude - $fromLongitude);
		$fromLatitudeRadians = deg2rad($fromLatitude);
		$toLatitudeRadians = deg2rad($toLatitude);

		$haversine = sin($latitudeDifference / 2) ** 2
			+ cos($fromLatitudeRadians)
			* cos($toLatitudeRadians)
			* sin($longitudeDifference / 2) ** 2;

		return round(
			2 * $earthRadiusKilometers * asin(min(1, sqrt($haversine))),
			2
		);
	}

	private static function buildCoordinateComputationResult(
		float $distanceKilometers,
		int $durationMinutes,
		string $origin,
		string $destination
	): array {
		$distanceKilometers = round(max(0, $distanceKilometers), 2);
		$data = [
			'status' => 1,
			'distance' => number_format($distanceKilometers, 2, '.', '') . 'km',
			'duration' => max(0, $durationMinutes),
			'origin' => $origin,
			'destination' => $destination,
			'rate' => self::getRateFromDistanceKilometers($distanceKilometers),
		];
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
