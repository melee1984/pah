<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Orders\Orders;
use App\Model\Cart;
use Auth;

class OrderController extends Controller
{
        //
    public function getList() 
    {	
        $totalSummary = array();
        $qty =0;
        $fee =0;
        $sub_total =0;
        $total =0;
        $discount =0;
        $total_comm = 0;
        $total_net = 0;

        $orders = Orders::with('cart')
            ->with('partner')
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereNotNull('submitted_at') 
            ->whereNull('delivered_at')
            ->orderBy('created_at', 'desc')->get();

         foreach($orders as $order) {

            $order->rider;
            $order->status;
            $order->cart->address;
            $order->cart->partnerlocation;

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

        if ($request->has('dateFilter')) {
            $dataFilterArray = explode('-', $request->input('dateFilter'));    
            $orders = Orders::with('cart')
                ->with('partner')
                ->wherePartnerId(Auth::User()->merchant->id)
                ->whereNotNull('submitted_at') 
                ->whereNull('delivered_at')
                ->where('submitted_at','>=', date('Y-m-d G:i', strtotime($dataFilterArray[0]) ))
                ->where('submitted_at','<=', date('Y-m-d G:i', strtotime($dataFilterArray[1]) ))
                ->orderBy('created_at', 'asc')
                ->get();
        }
        else {

            $orders = Orders::with('cart')
                ->with('partner')
                ->wherePartnerId(Auth::User()->merchant->id)
                ->whereNotNull('submitted_at') 
                ->whereNull('delivered_at')
                ->whereDay('submitted_at', '=', date('d'))
                // ->whereMonth('submitted_at', '=', date('m'))
                ->orderBy('created_at', 'asc')
                ->get();
        }

        
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

        return response()->json($data, 200);


    }

    public function orderSummary() {

        $totalSales = 0;

        $pending = Orders::with('cart')
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereStatusId(1)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->count();

        $onGoingOrder = Orders::with('cart')
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereStatusId(3)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->count();

        $completed = Orders::with('cart')
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereStatusId(7)
            ->whereNotNull('submitted_at') 
            ->orderBy('created_at', 'asc')
            ->get();

        $cancelled = Orders::with('cart')
            ->wherePartnerId(Auth::User()->merchant->id)
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
