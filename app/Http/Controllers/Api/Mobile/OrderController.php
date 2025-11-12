<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Orders\Orders;
use App\Model\Orders\OrderProcess;

use App\User;

class OrderController extends Controller
{
     public function index(Orders $order, Request $request) {

    	$data = array();	

    	$order->cart;
    	$order->status;
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
    public function updateOrderStatus(Orders $order, Request $request) {

        $user = $request->user();

        $status_id = "";

        if ($request->input('status')!=null) {
            
            if ($request->input('status') =="cancel") {
                $status_id = 8;
                $order->status_id = $status_id; // Cancelled 
            }

            $status = $order->save();

            if ($status) {
                
                OrderProcess::create([
                    'status_id' => $order->status_id,
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                ]);
                
                $data['message'] = "We have receive your request and it was successfully changed.";
                $data['status'] = 1;
            }
            else {
                $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
                $data['status'] = 0;    
            }

        }
        else {
           $data['message'] = "We could not find your request. Please try agian.";
        $data['status'] = 0;  
        }

       

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
        $order->cart->address;
        $order->status;  
        $order->logs = $order->getActionLogs();
       
        
        $data['cart'] = $order;

        return response()->json($data, 200);

    }

   

}
