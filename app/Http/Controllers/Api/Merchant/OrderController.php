<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Orders\Orders;
use App\Model\Cart;
use Carbon\Carbon;
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

        $orders = Orders::with([
                'cart',
                'cart.address',
                'cart.partnerlocation',
                'partner',
            ])
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereNotNull('submitted_at') 
            ->whereNull('delivered_at')
            ->orderBy('created_at', 'desc')->get();

         foreach($orders as $order) {

            $order->rider;
            $order->status;
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
        $orders = Orders::with(['cart.details', 'status'])
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->get();

        $orders->each(function ($order) {
            $summary = $order->cart ? $order->cart->cartItemSummary() : [];
            $order->dashboard_total = (float) str_replace(',', '', (string) ($summary['total'] ?? 0));
            $order->dashboard_commission = (float) str_replace(',', '', (string) ($summary['total_comm'] ?? 0));
        });

        $completed = $orders->where('status_id', 7);
        $now = now();
        $salesForPeriod = function (Carbon $start) use ($completed, $now) {
            return $completed->filter(function ($order) use ($start, $now) {
                $date = $order->delivered_at ?: $order->submitted_at;

                return $date && Carbon::parse($date)->between($start, $now);
            })->sum('dashboard_total');
        };

        $salesTrend = collect(range(6, 0))->map(function ($daysAgo) use ($completed, $now) {
            $date = $now->copy()->subDays($daysAgo);
            $sales = $completed->filter(function ($order) use ($date) {
                $completedAt = $order->delivered_at ?: $order->submitted_at;

                return $completedAt && Carbon::parse($completedAt)->isSameDay($date);
            })->sum('dashboard_total');

            return ['label' => $date->format('D'), 'sales' => $sales];
        })->values();

        $grossRevenue = (float) $completed->sum('dashboard_total');
        $commission = (float) $completed->sum('dashboard_commission');
        $data['record'] = [
            'pendingOrder' => $orders->where('status_id', 1)->count(),
            'onGoingOrder' => $orders->whereBetween('status_id', [2, 6])->count(),
            'completed' => $completed->count(),
            'cancelled' => $orders->where('status_id', 8)->count(),
            'totalOrders' => $orders->count(),
            'salesToday' => $salesForPeriod($now->copy()->startOfDay()),
            'salesWeek' => $salesForPeriod($now->copy()->startOfWeek()),
            'salesMonth' => $salesForPeriod($now->copy()->startOfMonth()),
            'grossRevenue' => $grossRevenue,
            'commission' => $commission,
            'netRevenue' => $grossRevenue - $commission,
            'averageOrder' => $completed->count() ? $completed->avg('dashboard_total') : 0,
            'completionRate' => $orders->count() ? ($completed->count() / $orders->count()) * 100 : 0,
            'salesTrend' => $salesTrend,
        ];

        return response()->json($data, 200);

    }


}
