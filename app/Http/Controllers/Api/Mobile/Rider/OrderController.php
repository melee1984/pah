<?php

namespace App\Http\Controllers\Api\Mobile\Rider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Session;
use Validator;

use App\Model\Orders\Orders;
use App\Model\Orders\OrderProcess;
use Carbon\Carbon;
use App\Model\Bookings\BookingOrderProcess;

use App\PushNotification;

use App\Model\Bookings\Bookings;


class OrderController extends Controller
{
     public function bookings(Request $request) {

     	$data = array();
     	$dataContainer = array();

     	$orders = Orders::whereRiderId($request->user()->rider->id)
    					->whereNull('accepted_at')
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
	    	 			->get();
						

		foreach($orders as $order) {

			$order->job_order_format = "JO " . $order->cart->order_no;
			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$product_items = $order->cart->cartItemList();    
			$order->cart_total = $order->cart->cartItemTotal();
			
			foreach($product_items as $list) {
			    $list->variance_content = unserialize($list->variance_content);

			    if ($list->item) {
			        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
			    }
			}

			$order->status;  
			$order->submitted_at_ = date("d-m-Y h:ia", strtotime($order->submitted_at));
			$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

			$order->logs = $order->getActionLogs();
			$order->option = 1;

			array_push($dataContainer, $order);
		}


		// Getting the bookings 
		$bookings = Bookings::whereRiderId($request->user()->rider->id)
                        ->orderBy('created_at', 'desc')
                        ->whereNull('accepted_at')
                        ->get();

        foreach($bookings as $booking) {
            
            $booking->job_order_format = "JO " . $booking->job_order;
			$booking->created_at_format = date("D, d M h:ia", strtotime($booking->created_at));

            $booking->booking_date_and_time_format = date('F d, Y', strtotime($booking->booking_date)) . " @ " .  date('h:ia', strtotime($booking->booking_time));
            $booking->delivery_rate_format = number_format($booking->delivery_rate, 2);
            $booking->dropoff;
            $booking->pickup;
            $booking->status;
            $booking->logs = $booking->getActionLogs();
			$booking->option = 2;

            array_push($dataContainer, $booking);
            
        }

		// array_push($dataContainer, array('booking'=>'YES'));
		$data['data'] = $dataContainer;

    	return response()->json($data, 200);

    }
    public function getAcceptedBooking(Request $request) {

    	$data = array();
     	$dataContainer = array();

    	$orders = Orders::whereRiderId($request->user()->rider->id)
    					->whereNotNull('accepted_at')
    					->whereAcceptedByRiderId($request->user()->rider->id)
    					->whereDate('submitted_at', Carbon::today())
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();
							

		foreach($orders as $order) {
			
			$order->job_order_format = "JO " . $order->cart->order_no;
			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$product_items = $order->cart->cartItemList();    
			$order->cart_total = $order->cart->cartItemTotal();
			
			foreach($product_items as $list) {
			    $list->variance_content = unserialize($list->variance_content);

			    if ($list->item) {
			        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
			    }
			}

			$order->status;  
			$order->submitted_at_ = date("d-m-Y h:ia", strtotime($order->submitted_at));
			$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

			$order->logs = $order->getActionLogs();
			$order->option = 1;

			array_push($dataContainer, $order);
		}

		// Getting the bookings 
		$bookings = Bookings::whereRiderId($request->user()->rider->id)
						->whereNotNull('accepted_at')
						->whereAcceptedByRiderId($request->user()->rider->id)
						->orderBy('created_at', 'desc')
                        ->get();

        foreach($bookings as $booking) {
            
			$booking->job_order_format = "JO " . $booking->job_order;
			$booking->created_at_format = date("D, d M h:ia", strtotime($booking->created_at));

            $booking->booking_date_and_time_format = date('F d, Y', strtotime($booking->booking_date)) . " @ " .  date('h:ia', strtotime($booking->booking_time));
            $booking->delivery_rate_format = number_format($booking->delivery_rate, 2);
            $booking->dropoff;
            $booking->pickup;
            $booking->status;
            $booking->logs = $booking->getActionLogs();
			$booking->option = 2;

            array_push($dataContainer, $booking);
            
        }

		// array_push($dataContainer, array('booking'=>'YES'));
		$data['data'] = $dataContainer;

    	return response()->json($data, 200);
    }

    public function getAcceptedBookingByDate(Request $request) {

    	$data = array();
     	$dataContainer = array();

    	if ($request->input('day_date')=="") {
    		$day = date('Y-m-d');
    	}
    	else {
    		$day = date('Y-m-d', strtotime($request->input('day_date')));
    	}

    	$orders = Orders::whereRiderId($request->user()->rider->id)
    					->whereNotNull('accepted_at')
    					->whereAcceptedByRiderId($request->user()->rider->id)
    					->whereDate('submitted_at', $day)
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->paginate(10);
						
		foreach($orders as $order) {
			$order->job_order_format = "JO " . $order->cart->order_no;
			$order->summary = $order->cart->cartItemSummary();
			$order->cart->address;
			$order->cart->payment;
			$order->cart->partnerlocation;
			$product_items = $order->cart->cartItemList();    
			$order->cart_total = $order->cart->cartItemTotal();
			
			foreach($product_items as $list) {
			    $list->variance_content = unserialize($list->variance_content);

			    if ($list->item) {
			        $list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
			    }
			}

			$order->status;  
			$order->submitted_at_ = date("d-m-Y h:ia", strtotime($order->submitted_at));
			$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

			$order->logs = $order->getActionLogs();
			$order->option = 1;

			array_push($dataContainer, $order);
		}


    	$bookings = Bookings::whereRiderId($request->user()->rider->id)
						->whereNotNull('accepted_at')
						->whereAcceptedByRiderId($request->user()->rider->id)
						->whereDate('created_at', $day)
						->orderBy('created_at', 'desc')
                        ->get();

        foreach($bookings as $booking) {
            
			$booking->job_order_format = "JO " . $booking->job_order;
			$booking->created_at_format = date("D, d M h:ia", strtotime($booking->created_at));

            $booking->booking_date_and_time_format = date('F d, Y', strtotime($booking->booking_date)) . " @ " .  date('h:ia', strtotime($booking->booking_time));
            $booking->delivery_rate_format = number_format($booking->delivery_rate, 2);
            $booking->dropoff;
            $booking->pickup;
            $booking->status;
            $booking->logs = $booking->getActionLogs();
			$booking->option = 2;

            array_push($dataContainer, $booking);
            
        }

		// array_push($dataContainer, array('booking'=>'YES'));
		$data['data'] = $dataContainer;

    	return response()->json($data, 200);
    }


    public function acceptBooking(Orders $order, $action, Request $request) {
		
		$data = array();

		if ($order) {

			$user = $request->user();

			if ($action == "accept") {

				$order->accepted_by_rider_id = $user->rider->id;
				$order->accepted_at = now();
				$status = $order->save();
				$data['status'] = 1;


			} else if ($action == "pickup") {

				$order->status_id = 5;
				$order->save();

				OrderProcess::updateOrCreate([
                    'status_id' => 5, // item pickup  
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

                PushNotification::sendPushOrder($order);

                $data['status'] = 1;

			}
			else if ($action == "delivered") {

				// Item Pickup 
				$order->status_id = 6;
				$order->save();

				OrderProcess::updateOrCreate([
                    'status_id' => 6, // item pickup  
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

				// Item Delivered 
                $order->status_id = 7;
				$order->save();

				PushNotification::sendPushOrder($order);

				OrderProcess::updateOrCreate([
                    'status_id' => 7, // item pickup  
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

                $data['status'] = 1;
			}


		}
		else {

			$data['status'] = 0;
			$data['message'] = "Unable to find booking. Please try again";
		}

        return response()->json($data, 200);

    }

    public function acceptJobBooking(Bookings $booking, $action, Request $request) {

    	$data = array();

		if ($booking) {

			$user = $request->user();

			if ($action == "accept") {

				$booking->accepted_by_rider_id = $user->rider->id;
				$booking->accepted_at = now();
				$status = $booking->save();

				BookingOrderProcess::updateOrCreate([
                    'status_id' => 2, // item pickup  
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ]);

                BookingOrderProcess::updateOrCreate([
                    'status_id' => 3, // item pickup  
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ]);

                PushNotification::sendPushOrder($booking,1);
                $data['status'] = 1;

			} else if ($action == "pickup") {

				$booking->status_id = 4;
				$booking->save();
				BookingOrderProcess::updateOrCreate([
                    'status_id' => 4, // Item Pickup   
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ]);

                PushNotification::sendPushOrder($booking,1);
                $data['status'] = 1;

			}
			else if ($action == "delivered") {

				// Item Pickup 
				$booking->status_id = 6;
				$booking->save();

				BookingOrderProcess::updateOrCreate([
                    'status_id' => 5, // Item Pickup   
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ]);

				BookingOrderProcess::updateOrCreate([
                    'status_id' => 6, // item pickup  
                    'booking_id' => $booking->id,
                    'user_id' => $user->id,
                ]);

				// Item Delivered 
                $booking->status_id = 6;
				$booking->save();

                PushNotification::sendPushOrder($booking,1);
                $data['status'] = 1;
			}


		}
		else {

			$data['status'] = 0;
			$data['message'] = "Unable to find booking. Please try again";
		}

        return response()->json($data, 200);

    }

     public function saveTokenDevice(Request $request) {
        	
        if ($request->input('token')!="") {
        	$user = $request->user();
	        $user->device_token = $request->input('token');
	        $user->device_id = $request->input('device');
	        $user->save();
			return response()->json(['token saved successfully.']);
        }
        else {
        	return response()->json(['Token is empty']);
        }

    }

    public function saveTokenDeviceFood(Request $request) {
        
        if ($request->input('token')!="") {
			$user = $request->user();
	        $user->device_token_food = $request->input('token');
	        $user->device_id = $request->input('device');
	        $user->save();

	        return response()->json(['token saved successfully.']);
        }
        else {
        	return response()->json(['Token is empty']);
        }


    }

    public function saveTokenDeviceStore(Request $request) {
        
    	if ($request->input('token')!="") {
			$user = $request->user();
	        $user->device_token_store = $request->input('token');
	        $user->device_id = $request->input('device');
	        $user->save();
	        return response()->json(['token saved successfully.']);
        }
        else {
        	return response()->json(['Token is empty']);
        }

    }

}
