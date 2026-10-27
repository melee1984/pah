<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\LibraryStatus;
use App\Model\Bookings\BookingStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use App\Model\Orders\Orders;
use App\Model\Orders\OrderProcess;
use App\Services\RiderOfferDispatcher;

use App\User;

class OrderController extends Controller
{
     public function index(Orders $order, Request $request) {

    	$data = array();	

        $order->cart;
        $order->cart->diningTable;
    	$order->status;
    	$data['order'] = $order;

    	return response()->json($data, 200);

    }

    public function getOrderById(Orders $order, Request $request) {

        $data = array();        

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
        $order->submitted_at_ = date("d-m-Y G:ia", strtotime($order->submitted_at));
        $order->formated_submitted_at_ = date("D, d M G:ia", strtotime($order->submitted_at));
        $order->cart->address;
        $order->cart->payment;
        $order->cart->diningTable;
        $order->status;  
        $order->load('rider.location');
        $order->logs = $order->getActionLogs();

        $data['order'] = $order;

        return response()->json($data, 200);

    }

    public function orders(Request $request) {

    	$data = array();	

    	$orders = Orders::whereUserId($request->user()->id)
                    ->orderby('submitted_at','desc')
                    ->get();

    	foreach($orders as $order) {

            if (!$order->cart->option_id) {
                $order->cart->diningTable;
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
                $order->rider;
                $order->logs = $order->getActionLogs();
            }
            else {


            }
    	}
    	
    	$data['orders'] = $orders;

    	return response()->json($data, 200);

    }

     /**
     * Update Order Status 
     * @param  Request $request [description]
     * @param  Orders  $order   [description]
     * @return [type]           [description]
     */
    public function updateOrderStatus(Orders $order, Request $request)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:cancel'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);
        $user = $request->user();

        $result = DB::transaction(function () use ($order, $user) {
            $order = Orders::query()
                ->whereKey($order->getKey())
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $order) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Order not found.',
                ], 404)];
            }

            if ((int) $order->order_status_id === LibraryStatus::STATUS_CANCELLED) {
                return [
                    'order' => $order,
                    'already_cancelled' => true,
                ];
            }

            if (! in_array((int) $order->order_status_id, [
                LibraryStatus::STATUS_ORDER_PLACED,
                LibraryStatus::STATUS_ORDER_ACCEPTED,
                LibraryStatus::STATUS_PROCESSING,
                LibraryStatus::STATUS_READY_FOR_PICKUP,
            ], true)) {
                return ['response' => response()->json([
                    'status' => 0,
                    'message' => 'Orders already picked up or completed cannot be cancelled.',
                ], 409)];
            }

            $order->order_status_id = LibraryStatus::STATUS_CANCELLED;
            $order->booking_status_id = $order->cart?->requiresDelivery()
                ? BookingStatus::STATUS_BOOKING_CANCELLED
                : null;
            $order->save();

            OrderProcess::updateOrCreate([
                'status_id' => LibraryStatus::STATUS_CANCELLED,
                'order_id' => $order->id,
            ], [
                'user_id' => $user->id,
            ]);

            app(RiderOfferDispatcher::class)->cancelOrder((int) $order->id);

            return [
                'order' => $order,
                'already_cancelled' => false,
            ];
        });

        if (isset($result['response'])) {
            return $result['response'];
        }

        Log::info('Customer cancelled an order.', [
            'order_id' => $result['order']->id,
            'user_id' => $user->id,
            'reason' => $validated['reason'] ?? null,
            'already_cancelled' => $result['already_cancelled'],
        ]);

        return response()->json([
            'status' => 1,
            'message' => $result['already_cancelled']
                ? 'Order was already cancelled.'
                : 'Order cancelled successfully.',
            'order_id' => $result['order']->id,
            'order_status_id' => $result['order']->order_status_id,
        ]);
    }
}
