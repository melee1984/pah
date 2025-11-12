<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Orders\Orders;
use App\Model\Cart;
use App\Model\Riders;
use App\LibraryStatus;
use App\Model\Orders\OrderProcess;
use App\Partners;
use Auth;
use Validator;
use App\User;

use App\PushNotification;
class OrderController extends Controller
{
   
    public function getList() 
    {	 
         $orders = Orders::with('cart')
            ->whereNotNull('submitted_at') 
            ->whereNull('delivered_at')
            // ->whereRaw('status_id != 8')
            ->with('partner')
            ->orderBy('created_at', 'desc')->get();

        foreach($orders as $order) {

            $order->rider;
        	$order->status;
            $order->cart->address;
            $order->cart->partnerlocation;

            $order->submitted_date = $order->created_at->format('m/d/Y h:i a');
            $order->summary = $order->cart->cartItemSummary();
            $order->cart->cartItemVariance();
        }

        $data['orders'] = $orders;
        $data['riders'] = Riders::active()->get();
        $data['statuses'] = LibraryStatus::orderBy('sorting','asc')->get();

    	return response()->json($data, 200);
    }   

     public function getListwithFilter(Request $request) {

        $totalSummary = array();
        $qty =0;
        $fee =0;
        $sub_total =0;
        $total =0;
        $discount =0;
        $total_comm = 0;
        $total_net = 0;
        $orders = array();

        $merchant = "";

        $query = Orders::query();
     
        if ($request->has('dateFilter')) {

            $dataFilterArray = explode('-', $request->input('dateFilter'));    

            $query->with('cart')
                ->with('partner')
                ->whereNotNull('submitted_at') 
                ->whereNull('delivered_at')
                ->where('submitted_at','>=', date('Y-m-d G:i', strtotime($dataFilterArray[0]) ))
                ->where('submitted_at','<=', date('Y-m-d G:i', strtotime($dataFilterArray[1]) ))
                ->orderBy('created_at', 'asc');

            // $query = Orders::with('cart')
            //     ->with('partner')
            
        }
        else {

            // $query = Orders::with('cart')
            //     ->with('partner')
            //     ->whereNotNull('submitted_at') 
            //     ->whereNull('delivered_at')
            //     ->whereDay('submitted_at', '=', date('d'))
            //     ->orderBy('created_at', 'asc');
               
             $query->with('cart')
                    ->with('partner')
                    ->whereNotNull('submitted_at') 
                    ->whereNull('delivered_at')
                    ->whereDay('submitted_at', '=', date('d'))
                    ->orderBy('created_at', 'asc');
        }

        if ($request->has('merchant')) { 
            if ($request->input('merchant')!="") {
                $query->where('partner_id', '=', $request->input('merchant'));    
            }
        }


        $orders = $query->get();


        if (!$orders) return response()->json($data, 200);


        foreach($orders as $order) {

            $order->rider;
            $order->status;
            $order->cart->address;

            $order->submitted_date = $order->created_at->format('m/d/Y h:i a');
            $summary= $order->cart->cartItemSummary();
            
            $order->cart->cartItemVariance();

            $qty+= (int)$summary['qty'];
            $fee+= number_format((float)$summary['delivery_fee'],2);
            $sub_total+= number_format((float)$summary['sub_total'],2);
            $total+= number_format((float)$summary['total'],2);
            $discount+= number_format((float)$summary['discount'],2);
            $total_comm += number_format((float)$summary['total_comm'],2);
            $total_net += number_format((float)$summary['total'] - (float)$summary['total_comm'],2);

            $order->summary = $summary;
        }

        $totalSummary['qty'] = $qty;
        $totalSummary['fee'] = number_format($fee,2);
        $totalSummary['discount'] = number_format($discount,2);
        $totalSummary['sub_total'] = number_format($sub_total,2);
        $totalSummary['total'] = number_format($total,2);
        $totalSummary['total_comm'] = number_format($total_comm,2);
        $totalSummary['total_net'] = number_format($total_net,2);
        
        $data['totalSummary'] = $totalSummary;
        $data['orders'] = $orders;
        $data['partners'] = Partners::whereNotNull('verified_at')->whereActive(1)->get(['id','restaurant_name']);

        return response()->json($data, 200);

    }

    public function getListMemberwithFilter() {

        $data = array();

        $users =  User::select('id', 'created_at', 'firstname', 'lastname', 'mobile', 'email')
                    ->orderBy('created_at', 'desc')
                    ->paginate(100);

        foreach($users as $user) {

            $user->created_at_format = date('F d, Y', strtotime($user->created_at));
        }

        $data['members'] = $users;
        return response()->json($data, 200);

    }
    public function getListMerchantwithFilter() {

        $data = array();

        $users =  Partners::orderBy('created_at', 'desc')
                    ->paginate(100);

        foreach($users as $user) {

            $user->created_at_format = date('F d, Y', strtotime($user->created_at));
            $user->isverified = $user->verified_at?1:0;
            $user->istoreopen = $user->store_open?1:0;
            $user->accoutType;
        }

        
        $data['members'] = $users;

        return response()->json($data, 200);
    }

    
    /**
     * Update Order Status 
     * @param  Request $request [description]
     * @param  Orders  $order   [description]
     * @return [type]           [description]
     */
    public function updateOrderStatus(Request $request, Orders $order) {

            if ($request->input('status_id')!=null) {
                $order->status_id = $request->input('status_id');    
            }
            else {
                $order->status_id = "";
            }
            $status = $order->save();

            if ($status) {

                OrderProcess::create([
                    'status_id' => $order->status_id,
                    'order_id' => $order->id,
                    'user_id' => Auth::User()->id,
                ]);

                PushNotification::sendPushOrder($order);
                
                $data['message'] = "Successfully updated status";
                $data['status'] = 1;
            }
            else {
                $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
                $data['status'] = 0;    
            }

        return response()->json($data, 200);
    }
    public function updateOrderRider(Request $request, Orders $order) {

        if ($request->input('rider_id')!=null) {
            $order->rider_id = $request->input('rider_id');    
        }
        else {
            $order->rider_id = "";
        }
        
        $status = $order->save();

        if ($status) {

            // Send Push Notification to Rider 
          
            PushNotification::sendPushToRider($order);

            $data['message'] = "Successfully updated rider";
            $data['status'] = 1;
        }
        else {
            $data['message'] = "We've encounter some issue during process. Please refresh your page and try again. Thank you.";
            $data['status'] = 0;    
        }

        return response()->json($data, 200);
    }

    public function orderSummary() {

        $totalSales = 0;

        $pending = Orders::with('cart')
            ->whereStatusId(1)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->count();

        $onGoingOrder = Orders::with('cart')
            ->whereStatusId(3)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->count();

        $completed = Orders::with('cart')
            ->whereStatusId(7)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->get();

        $cancelled = Orders::with('cart')
            ->whereStatusId(8)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->count();


        foreach($completed as $order) {
            $cartTotal = $order->cart->cartItemTotal();
            $totalSales = $totalSales + $cartTotal;

        }

        $data['record'] = array(
            'pendingOrder' => $pending,
            'onGoingOrder' => $onGoingOrder,
            'completed' => $completed->count(),
            'cancelled' => $cancelled,
            'salesToday' => $totalSales,
        );

        return response()->json($data, 200);

    }


}
