<?php

namespace App\Http\Controllers\Api\Mobile\Booking;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Bookings\BookingDropoffInfo;
use App\Model\Bookings\BookingPickupInfo;
use App\Model\Bookings\Bookings;
use App\Model\Bookings\BookingOrderProcess;
use App\Model\Bookings\BookingStatus;
use App\Model\Riders;
use App\SmsNotification;

use Validator;

class BookingController extends Controller
{   
    public function index(Request $request) {

        $bookings = Bookings::whereUserId($request->user()->id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        foreach($bookings as $booking) {
            $booking->job_order_format = "Job Order " . $booking->job_order;
            $booking->booking_date_and_time_format = date('F d, Y', strtotime($booking->booking_date)) . " @ " .  date('H:i a', strtotime($booking->booking_time));
            $booking->delivery_rate_format = number_format($booking->delivery_rate, 2);
            $booking->dropoff;
            $booking->pickup;
            $booking->status;
            $booking->logs = $booking->getActionLogs();
        }

        $data['bookings'] = $bookings;

        return response()->json($data, 200);

    }
    public function booking(Request $request) {

    	$data = array();

    	$pickup = BookingPickupInfo::whereUserId($request->user()->id)->get();
    	$dropoff = BookingDropoffInfo::whereUserId($request->user()->id)->get();
    	
    	$data['pickup'] = $pickup;
    	$data['dropoff'] = $dropoff;

    	return response()->json($data, 200);
    }

    public function getUserAddresses(Request $request) {

        $data = array();

        $pickup = BookingPickupInfo::whereUserId($request->user()->id)->get();
        $dropoff = BookingDropoffInfo::whereUserId($request->user()->id)->get();
        
        $data['pickup'] = $pickup;
        $data['dropoff'] = $dropoff;

        return response()->json($data, 200);

    }
    
    public function getKilometerByCoodinates(Request $request) {

        // if ($request->user->isAdmin()) {
        //     $data['status'] = 1;
        //     $data['rate'] = array('distance' => '10KM', 'duration' => '6mins', 'origin' => 'far', 'destination' => 'Here', 'rate' => '100.00');
        //     $data['timings'] = Bookings::getBookingCalendar();

        //     return response()->json($data, 200);    
        // }

        $from_latlong = $request->input('pick_lat') .",". $request->input('pick_lng');
        $to_latlong = $request->input('del_lat') .",". $request->input('del_lng');

        $unit = 'K';
        // $apiKey = 'AIzaSyDbY3uYaRgP0cvJmW-wnalfqyUg2oK0ybk';
        // $apiKey = 'AIzaSyBSGeqs54fvHY42AS3-VuZr-D5agAuM43U';
        $apiKey = 'AIzaSyCBU3bFyvWzW3R0g0kqQsZxTaay7ImkO14'; // mobiel app;

        // $distance_data = file_get_contents('
        //     );


        $Url = 'https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins='.$from_latlong.'&destinations='.$to_latlong.'&key='.$apiKey;


        // $distance_arr = json_decode($distance_data);

        // $destination = $distance_arr->destination_addresses[0];
        // $origin = $distance_arr->origin_addresses[0];

        // $distance = $distance_arr->rows[0]->elements[0]->distance->text;
        // $duration = $distance_arr->rows[0]->elements[0]->duration->text;
        
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

        if (ceil($distance) <=3) {
            $rate = 55;
        }
        else {
            $rate = ((ceil($distance) - 3) * 10);   
            $rate = 55 + $rate;
        }

        $data['status'] = 1;
        $data['rate'] = array('distance' => number_format($distance,2) . " km",
                            'duration' => $duration, 
                            'origin' => $origin, 
                            'destination' => $destination, 
                            'rate' => number_format($rate,2));

        $data['timings'] = Bookings::getBookingCalendar();


        return response()->json($data, 200);

    }

    public function store(Request $request) {

        $data = array();
        $rules = [
          'pickup_id'     => 'required',
          'dropoff_id'    => 'required',
          'selected_date'    => 'required',
          'selected_time' => 'required',
          'delivery_array' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
         
            return response()->json([
                'message' => $validator->messages()->first(),
            ]);
          
        } 
        else {  

            if ($request->input('selected_date') == "Today") {
                $deliveryDate = Date('Y-m-d H:i:s');
            }
            else {
                $deliveryDate = date("Y-m-d H:i:s", strtotime($request->input('selected_date')));
            }

            $booking = Bookings::create([
                'booking_date' => $deliveryDate, 
                'booking_time' => date("Y-m-d H:i:s", strtotime($request->input('selected_time'))),
                'errand_type_id' => 1,
                'user_id' => $request->user()->id,
                'delivery_rate' => $request->input('delivery_array')['rate'],
                'duration' => $request->input('delivery_array')['duration'],
                'distance' => $request->input('delivery_array')['distance'],
                'pickup_id' => $request->input('pickup_id'),
                'dropoff_id' => $request->input('dropoff_id'),
                'cod' => $request->input('cod'), // Place Order 
                'item_info' =>$request->input('item_info'),
                'instruction' => $request->input('instruction'),
                'map_info' => json_encode($request->input('delivery_array')),
                'job_order' => Bookings::generateOrderNo(),
                'active' => 0, 
                'status_id' => 1,
            ]);

            if ($booking) {

                BookingOrderProcess::create([
                    'booking_id' => $booking->id,
                    'status_id' => 1,
                ]);

                // Notify User
                SmsNotification::sendNewOrder();

                $data['status'] = 1;
                $data['message'] = "Successfully created new booking";
            }

            $data['booking'] = $booking;
        }
          
         return response()->json($data, 200);
    	
    }

    /**
     * Cancell Booking 
     */
    public function cancelBooking(Request $request, Bookings $booking) {

        if ($request->input('status')=="cancel") {
            $booking->status_id = 7;
        }
        else {
            $booking->status_id = "";
        }
        $status = $booking->save();

        if ($status) {

            BookingOrderProcess::create([
                'status_id' => $booking->status_id,
                'booking_id' => $booking->id,
                'user_id' => $request->user()->id,
            ]);
            
            $data['message'] = "Successfully updated status";
            $data['status'] = 1;
        }
        else {
            $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
            $data['status'] = 0;    
        }

        return response()->json($data, 200);
    }

    public function getBookingById(Request $request, Bookings $booking) {

        $data = array();        

        if (!$booking) {
            $data['status'] = 0;
            $data['message'] = "Unable to find the booking";
            return;
        }

        $booking->job_order_format = "Job Order " . $booking->job_order;
        $booking->booking_date_and_time_format = date('F d, Y', strtotime($booking->booking_date)) . " @ " .  date('H:i a', strtotime($booking->booking_time));
        $booking->delivery_rate_format = number_format($booking->delivery_rate, 2);
        $booking->dropoff;
        $booking->pickup;
        $booking->status;
        $booking->logs = $booking->getActionLogs();

        $data['status'] = 1;
        $data['booking'] = $booking;
        return response()->json($data, 200);

    }

    public function deleteBookingAddress(Request $request) {

        $address = array();
        $data = array();

        if ($request->input('option') == 1) {

            // Checkif the address is associate with the current booking// 
            

            if (!Bookings::wherePickupId($request->input('address_id'))->exists()) {
                $bookingPickupInfo = BookingPickupInfo::whereUserId($request->user()->id)
                                ->whereId($request->input('address_id'));
                $bookingPickupInfo->delete();
                $address = $request->user()->pickupaddress;
                $data['address'] = $address;
                $data['status'] = 1;
                $data['message'] = "Successfully deleted address";
            }
            else {

                $data['message'] = "Unable to delete this address. The address is currently associate with your previous booking.";
                $data['status'] = 0;
            }
        }
        else {

             if (!Bookings::whereDropoffId($requst->input('address_id'))->exists()) {
                $bookingPickupInfo = BookingDropoffInfo::whereUserId($request->user()->id)
                                ->whereId($request->input('address_id'));
                $bookingPickupInfo->delete();
                $address = $request->user()->dropoffaddress;
                $data['address'] = $address;

                $data['status'] = 1;
                $data['message'] = "Successfully deleted address";
            }
            else {

                $data['message'] = "Unable to delete this address. The address is currently associated with your previous booking.";
                $data['status'] = 0;
            }
        }


        return response()->json($data, 200);
    }

}
