<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Model\Bookings\BookingDropoffInfo;
use App\Model\Bookings\BookingPickupInfo;
use App\Model\Bookings\Bookings;
use App\Model\Bookings\BookingOrderProcess;
use App\Model\Bookings\BookingStatus;
use App\Model\Riders;
use Auth;
use App\PushNotification;
use Validator;

class BookingController extends Controller
{   
    public function index() {

        $data = array();
        $data['dropoff'] = BookingDropoffInfo::whereUserId(0)
                            ->get();
        $data['pickup'] = BookingPickupInfo::whereUserId(0)
                            ->get();
        $data['status'] = 1;

        return response()->json($data, 200);
    }
    public function getList() 
    {	 
          $bookings = Bookings::orderBy('created_at', 'desc')
           				// ->whereRaw('status_id != 7')
          				->get();

        foreach($bookings as $booking) {
            $booking->job_order_format = "Job Order " . $booking->job_order;
            $booking->booking_date_and_time_format = date('F d, Y', strtotime($booking->booking_date)) . " @ " .  date('h:i a', strtotime($booking->booking_time));
            $booking->delivery_rate_format = number_format($booking->delivery_rate, 2);
            $booking->dropoff;
            $booking->pickup;
            $booking->status;
            $booking->rider;
            $booking->logs = $booking->getActionLogs();
        }

        $data['bookings'] = $bookings;
        $data['riders'] = Riders::active()->get();
        $data['statuses'] = BookingStatus::orderBy('sorting','asc')->get();

    	return response()->json($data, 200);
    }   

     public function updateOrderRider(Request $request, Bookings $booking) {


        $booking->rider_id = $request->input('rider_id');    
        $status = $booking->save();

        if ($status) {

            // Send Push Notification to Rider 
           PushNotification::sendPushToRider($booking, 2);

            $data['message'] = "Successfully updated rider";
            $data['status'] = 1;
        }
        else {
            $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
            $data['status'] = 0;    
        }

        return response()->json($data, 200);
    }

    public function updateOrderStatus(Request $request, Bookings $booking) {

            if ($request->input('status_id')!=null) {
                $booking->status_id = $request->input('status_id');    
            }
            else {
                $booking->status_id = "";
            }
            $status = $booking->save();

            if ($status) {

                BookingOrderProcess::create([
                    'status_id' => $booking->status_id,
                    'booking_id' => $booking->id,
                    'user_id' => Auth::User()->id,
                ]);

                PushNotification::sendPushOrder($booking, 2);
                
                $data['message'] = "Successfully updated status";
                $data['status'] = 1;
            }
            else {
                $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
                $data['status'] = 0;    
            }

        return response()->json($data, 200);
    }


    public function addressStore(Request $request) {

        $rules = [
          'name'    => 'required',
          'address' => 'required',
          'mobile' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()->first(),
            ]);
        } 
        else {
            $user = $request->user();

            if ($request->input('option') == 1) {
                $address = BookingPickupInfo::updateOrCreate([
                        'address' =>$request->input('address'),
                        'name' => $request->input('name'),
                        'mobile' =>$request->input('mobile'),
                        'landmark' => $request->input('landmark'),
                        'user_id' =>0,
                ]);
            }
            else {
                $address = BookingDropoffInfo::updateOrCreate([
                        'address' =>$request->input('address'),
                        'name' => $request->input('name'),
                        'mobile' =>$request->input('mobile'),
                        'landmark' => $request->input('landmark'),
                        'user_id' => 0,
                ]);

            }

            $data['address'] = $address;
            $data['status'] = 1;
            $data['message'] = "Successfully created new address";

        }

        return response()->json($data, 200);
    }

    public function storeNewBooking(Request $request) {

         $rules = [
          'pickup_id'    => 'required',
          'dropoff_id' => 'required',
          'booking_date' => 'required',
          'booking_time' => 'required',
          'delivery_rate' => 'required',
          'payment' => 'required',
          'item_info' => 'required',
          'instruction' => 'required',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->messages()->first(),
            ]);
        } 
        else {

            $booking = Bookings::create([
                'booking_date' => date("Y-m-d H:i:s", strtotime($request->input('booking_date'))),
                'booking_time' => date("Y-m-d H:i:s", strtotime($request->input('booking_time'))),
                'errand_type_id' => 1,
                'user_id' => $request->user()->id,
                'delivery_rate' => $request->input('delivery_rate'),
                // 'duration' => $request->input('delivery_array')['duration'],
                // 'distance' => $request->input('delivery_array')['distance'],
                'pickup_id' => $request->input('pickup_id'),
                'dropoff_id' => $request->input('dropoff_id'),
                'cod' => $request->input('cod'), // Place Order 
                'item_info' =>$request->input('item_info'),
                'instruction' => $request->input('instruction'),
                // 'map_info' => json_encode($request->input('delivery_array')),
                'job_order' => Bookings::generateOrderNo(),
                'active' => 0, 
                'status_id' => 1,
            ]);

            if ($booking) {

                BookingOrderProcess::create([
                    'booking_id' => $booking->id,
                    'status_id' => 1,
                ]);

                $data['status'] = 1;
                $data['message'] = "Successfully created new booking";
            }

            $data['booking'] = $booking;

        }

        return response()->json($data, 200);

    }


  
}
