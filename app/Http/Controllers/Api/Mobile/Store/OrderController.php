<?php

namespace App\Http\Controllers\Api\Mobile\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Session;
use Validator;

use App\Model\Orders\Orders;
use App\Model\Orders\OrderProcess;
use Carbon\Carbon;

use App\PushNotification;
use Auth;

class OrderController extends Controller
{	

	 /** Display Orders by store  */
     public function bookings(Request $request) {

		$user = $request->user();

    	$orders = Orders::wherePartnerId($user->merchant->id)
    					->whereNull('store_accepted_at')
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();

		foreach($orders as $order) {

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
			$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
			$order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));

			$order->logs = $order->getActionLogs();

		}
		

    	return response()->json($orders, 200);

    }
    public function getAcceptedBooking(Request $request) {

    	$user = $request->user();

    	$orders = Orders::wherePartnerId($user->merchant->id)
    					->whereNotNull('store_accepted_at')
    					->whereAcceptedByStoreId($user->merchant->id)
    					->whereDate('submitted_at', Carbon::today())
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();
						
		foreach($orders as $order) {
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
			$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
            $order->formated_submitted_at_ = date("D, d M h:ia", strtotime($order->submitted_at));
			$order->logs = $order->getActionLogs();
		}
    	return response()->json($orders, 200);
    }

    public function getAcceptedBookingByDate(Request $request) {

    	if ($request->input('day_date')=="") {
    		$day = date('Y-m-d');
    	}
    	else {
    		$day = date('Y-m-d', strtotime($request->input('day_date')));
    	}

    	$user = $request->user();

    	$orders = Orders::wherePartnerId($user->merchant->id)
    					->whereNotNull('store_accepted_at')
    					->whereAcceptedByStoreId($user->merchant->id)
    					->whereDate('submitted_at', $day)
	    	 			->orderby('submitted_at','desc')
	    	 			->with('cart')
						->get();

		foreach($orders as $order) {
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
		}
    	return response()->json($orders, 200);
    }


	public function orders(Request $request) {
	
		$data = array();	
		
		\Log::info('Fetching Order from which Restaurant: ' . $request->user()->merchant->id);

		$orders = Orders::wherePartnerId($request->user()->merchant->id)
					->orderby('submitted_at','desc')
					->get();
		
		foreach($orders as $order) {

			if (!$order->cart->option_id) {
				$order->summary = $order->cart->cartItemSummary();
				$product_items = $order->cart->cartItemList();    
				$order->cart_total = $order->cart->cartItemTotal();
				foreach($product_items as $list) {
					$list->variance_content = unserialize($list->variance_content);

					if ($list->item) {
						$list->price = number_format($list->item->getPrice() + number_format($list->variance_total,2),2);
					}
				}

				$order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
				$order->formated_submitted_at_ = date("D, d M G:ia", strtotime($order->submitted_at));
				$order->cart->address;
				$order->status;  
				$order->user;
				$order->logs = $order->getActionLogs();
			}
		}
		
		$data['orders'] = $orders;

    	return response()->json($data, 200);

	}

    public function acceptBooking(Orders $order, $action, Request $request) {
		
		$data = array();

		if ($order) {

			$user = $request->user();

			if ($action == "accept") {

				$order->accepted_by_store_id = $user->merchant->id;
				$order->store_accepted_at = now();
				$order->status_id = 3;
				$status = $order->save();

				if ($status) {

					OrderProcess::updateOrCreate([
	                    'status_id' => 2, // item pickup  
	                    'order_id' => $order->id,
	                    'user_id' => $user->accepted_by_store_id,
	                ]);

					OrderProcess::updateOrCreate([
	                    'status_id' => 3, // Processing   
	                    'order_id' => $order->id,
	                    'user_id' => $user->accepted_by_store_id,
	                ]);

	                PushNotification::sendPushOrder($order);

				}

				$data['status'] = 1;


			} else if ($action == "readyforpickup") {

				$order->status_id = 4;
				$order->save();

				OrderProcess::updateOrCreate([
                    'status_id' => 4, // item pickup  
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);

                // PushNotification::sendPushOrder($order);

                $data['status'] = 1;

			}
		}
		else {

			$data['status'] = 0;
			$data['message'] = "Unable to find booking. Please try again";
		}

        return response()->json($data, 200);

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

	public function toggleStoreOnline(Request $request)
	{
		$merchant = Auth::user()->merchant;

		\Log::info('Toggle store online request for merchant ID: ' . $merchant->id, ['current_status' => $merchant->store_open]);

		if (! $merchant) {
			return response()->json([
				'status' => 0,
				'message' => 'Merchant not found.',
			], 404);
		}

		$merchant->store_open = ! (bool) $merchant->store_open;
    	$merchant->save();
		$merchant->refresh();

		return response()->json([
			'status' => 1,
			'message' => $merchant->store_open
				? 'Store is now online.'
				: 'Store is now offline.',
			'store_online' => $merchant->store_open,
		]);
	}

	public function products(Request $request) 
	{
		$merchant = Auth::user()->merchant;

		return response()->json([
			'status' => 1,
			'products' => $merchant->products()->with('category')->get(),
		]);

	}	

	public function updateProductStatus(Request $request) 
	{
		$merchant = Auth::user()->merchant;

		$productId = $request->input('product_id');
		$newStatus = $request->input('status');

		if (! $productId || ! in_array($newStatus, [0, 1])) {
			return response()->json([
				'status' => 0,
				'message' => 'Invalid product ID or status.',
			], 400);
		}

		$product = $merchant->products()->find($productId);

		if (! $product) {
			return response()->json([
				'status' => 0,
				'message' => 'Product not found.',
			], 404);
		}

		$product->status = $newStatus;
		$product->save();

		return response()->json([
			'status' => 1,
			'message' => 'Product status updated successfully.',
			'product' => $product,
		]);

	}

}
