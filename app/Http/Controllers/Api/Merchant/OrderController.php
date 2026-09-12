<?php

namespace App\Http\Controllers\Api\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Model\Orders\Orders;
use App\Model\Cart;
use App\LibraryStatus;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
            ->orderBy('created_at', 'desc')->get();

        $deliveries = DB::table('rider_api_deliveries')
            ->whereIn('legacy_order_id', $orders->pluck('id'))
            ->get(['id', 'reference', 'legacy_order_id']);

        $proofsByDelivery = DB::table('rider_api_delivery_proofs')
            ->whereIn('delivery_id', $deliveries->pluck('id'))
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('delivery_id');

        $proofsByOrder = $deliveries
            ->groupBy('legacy_order_id')
            ->map(function ($orderDeliveries) use ($proofsByDelivery) {
                return $orderDeliveries->flatMap(function ($delivery) use ($proofsByDelivery) {
                    return $proofsByDelivery->get($delivery->id, collect())->map(function ($proof) use ($delivery) {
                        return [
                            'id' => $proof->reference,
                            'method' => $proof->method,
                            'processing_status' => $proof->processing_status,
                            'created_at' => $proof->created_at,
                            'file_url' => $proof->path
                                ? route('merchant.orders.delivery-proof', [
                                    'delivery' => $delivery->reference,
                                    'proof' => $proof->reference,
                                ])
                                : null,
                        ];
                    });
                })->values();
            });

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
            $order->delivery_proofs = $proofsByOrder->get($order->id, collect())->values();
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

    public function viewDeliveryProof(string $delivery, string $proof): StreamedResponse
    {
        $attachedProof = DB::table('rider_api_delivery_proofs as proofs')
            ->join('rider_api_deliveries as deliveries', 'deliveries.id', '=', 'proofs.delivery_id')
            ->join('order as orders', 'orders.id', '=', 'deliveries.legacy_order_id')
            ->where('orders.partner_id', Auth::User()->merchant->id)
            ->where('deliveries.reference', $delivery)
            ->where('proofs.reference', $proof)
            ->select('proofs.path')
            ->first();

        abort_if(! $attachedProof || ! $attachedProof->path, 404);
        abort_unless(Storage::disk('local')->exists($attachedProof->path), 404);

        return Storage::disk('local')->response($attachedProof->path);
    }

    public function getListwithFilter(Request $request) {

        $request->validate([
            'dateFilter' => ['sometimes', 'string', 'max:100'],
        ]);

        $totalSummary = array();
        $qty =0;
        $fee =0;
        $sub_total =0;
        $total =0;
        $discount =0;
        $total_comm = 0;
        $total_net = 0;
        $completedAt = DB::raw('COALESCE(delivered_at, submitted_at)');
        $query = Orders::with(['cart', 'cart.address', 'partner', 'rider', 'status'])
            ->wherePartnerId(Auth::User()->merchant->id)
            ->whereNotNull('submitted_at')
            ->where('status_id', LibraryStatus::STATUS_DELIVERED);

        if ($request->filled('dateFilter')) {
            $dateFilter = explode(' - ', $request->input('dateFilter'), 2);

            if (count($dateFilter) !== 2) {
                return response()->json(['message' => 'Please select a valid date and time range.'], 422);
            }

            try {
                $start = Carbon::parse(trim($dateFilter[0]));
                $end = Carbon::parse(trim($dateFilter[1]));
            } catch (\Throwable $exception) {
                return response()->json(['message' => 'Please select a valid date and time range.'], 422);
            }

            if ($start->greaterThan($end)) {
                return response()->json(['message' => 'The report start must be before its end.'], 422);
            }

            $query->whereBetween($completedAt, [$start, $end]);
        } else {
            $query->whereBetween($completedAt, [now()->startOfDay(), now()->endOfDay()]);
        }

        $orders = $query->orderByDesc($completedAt)->get();
        $number = fn ($value) => (float) str_replace(',', '', (string) $value);


        foreach($orders as $order) {

            $completedDate = $order->delivered_at ?: $order->submitted_at;
            $order->completed_date = Carbon::parse($completedDate)->format('m/d/Y h:i a');
            $summary= $order->cart->cartItemSummary();
            
            $order->cart->cartItemVariance();

            $qty+= (int)$summary['qty'];
            $fee += $number($summary['delivery_fee']);
            $sub_total += $number($summary['sub_total']);
            $total += $number($summary['total']);
            $discount += $number($summary['discount']);
            $total_comm += $number($summary['total_comm']);
            $total_net += $number($summary['total']) - $number($summary['total_comm']);

            $summary['net'] = number_format($number($summary['total']) - $number($summary['total_comm']), 2);
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
