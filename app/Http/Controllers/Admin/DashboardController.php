<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\Http\Controllers\Controller;
use App\LibraryStatus;
use App\Model\Orders\Orders;
use App\Partners;
use Carbon\Carbon;
use Illuminate\Http\Request;
use User;

use Auth;
class DashboardController extends Controller
{	
	/**
     * Login Merchant Account
     * @return view 
     */
    public function login() 
	{
		return view('dashboard.pages.login');
    }
    public function logout(Request $request) 
	{
	  Auth::logout();
	  return redirect('/data/login');
	}
    public function validateLogin(Request $request) 
	{	

		$request->validate( [
            'email' => 'required|email',
            'password' => 'required',
        ]);

	    $remember_me = $request->has('remember') ? true : false; 

	    if (auth()->attempt(['email' => $request->input('email'), 'password' => $request->input('password')], $remember_me))
	    {
	         $user = auth()->user();
	         if ($user->isAdmin()) {
	         	return redirect()->intended('data/dashboard');   	
	         }
	         else {
	        	return back()
	        	->with('display', 'alert-danger')
	        	->with('message','The access you have does not have role to access Admin partner dashboard. Please contact system administrator to fix this.')
	        	->withInput(); 	
	         }
	     	 
	    }
	    else{
	        return back()
	        	->with('display', 'alert-danger')
	        	->with('message','your username and password are wrong.')
	        	->withInput();
	    }

   	}	

   /**
     * Index Dashboard Page 
     * @return [type] [description]
     */
    public function index()
    {
        $agentMetrics = [
            'total' => Agent::query()->count(),
            'active' => Agent::query()->where('active', true)->count(),
            'setup_pending' => Agent::query()->where('must_change_password', true)->count(),
            'commission' => (float) AgentCommission::query()->earned()->sum('commission_amount'),
        ];

        $orders = Orders::query()
            ->with(['cart.details', 'partner', 'status'])
            ->whereNotNull('submitted_at')
            ->latest('submitted_at')
            ->get();

        $now = now();
        $startOfToday = $now->copy()->startOfDay();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfMonth = $now->copy()->startOfMonth();

        $orders->each(function ($order) {
            $summary = $order->cart ? $order->cart->cartItemSummary() : [];
            $order->dashboard_status_id = (int) ($order->order_status_id ?: $order->status_id);
            $order->dashboard_total = $this->moneyValue($summary['total'] ?? 0);
            $order->dashboard_commission = $this->moneyValue($summary['total_comm'] ?? 0);
        });

        $completedOrders = $orders->where('dashboard_status_id', LibraryStatus::STATUS_DELIVERED);
        $incomingOrders = $orders
            ->whereNotIn('dashboard_status_id', [LibraryStatus::STATUS_DELIVERED, LibraryStatus::STATUS_CANCELLED]);

        $salesForPeriod = function (Carbon $start) use ($completedOrders, $now) {
            return $completedOrders
                ->filter(function ($order) use ($start, $now) {
                    $date = $order->delivered_at ?: $order->submitted_at;

                    return $date && Carbon::parse($date)->between($start, $now);
                })
                ->sum('dashboard_total');
        };

        $dashboardMetrics = [
            'total_orders' => $orders->count(),
            'incoming_orders' => $incomingOrders->count(),
            'today_sales' => $salesForPeriod($startOfToday),
            'weekly_sales' => $salesForPeriod($startOfWeek),
            'monthly_sales' => $salesForPeriod($startOfMonth),
            'total_revenue' => $completedOrders->sum('dashboard_total'),
            'total_commission' => $completedOrders->sum('dashboard_commission'),
            'merchant_payout' => $completedOrders->sum('dashboard_total') - $completedOrders->sum('dashboard_commission'),
            'average_order' => $completedOrders->count() ? $completedOrders->avg('dashboard_total') : 0,
            'completion_rate' => $orders->count() ? ($completedOrders->count() / $orders->count()) * 100 : 0,
            'cancellation_rate' => $orders->count() ? ($orders->where('dashboard_status_id', LibraryStatus::STATUS_CANCELLED)->count() / $orders->count()) * 100 : 0,
            'active_merchants' => Partners::query()->where('active', true)->whereNotNull('verified_at')->count(),
        ];

        $statusBreakdown = $orders
            ->groupBy('dashboard_status_id')
            ->map(function ($statusOrders) use ($orders) {
                $status = $statusOrders->first()->status;

                return [
                    'title' => $status->title ?? 'Unknown',
                    'count' => $statusOrders->count(),
                    'percentage' => $orders->count() ? ($statusOrders->count() / $orders->count()) * 100 : 0,
                ];
            })
            ->sortByDesc('count')
            ->values();

        $topMerchants = $completedOrders
            ->groupBy('partner_id')
            ->map(function ($merchantOrders) {
                return [
                    'name' => $merchantOrders->first()->partner->restaurant_name ?? 'Unknown merchant',
                    'orders' => $merchantOrders->count(),
                    'revenue' => $merchantOrders->sum('dashboard_total'),
                ];
            })
            ->sortByDesc('revenue')
            ->take(5)
            ->values();

        $salesTrend = collect(range(6, 0))->map(function ($daysAgo) use ($completedOrders, $now) {
            $date = $now->copy()->subDays($daysAgo);
            $sales = $completedOrders
                ->filter(function ($order) use ($date) {
                    $completedAt = $order->delivered_at ?: $order->submitted_at;

                    return $completedAt && Carbon::parse($completedAt)->isSameDay($date);
                })
                ->sum('dashboard_total');

            return ['label' => $date->format('D'), 'sales' => $sales];
        });
        $salesTrendMax = max(1, (float) $salesTrend->max('sales'));

        $recentActivity = $orders->sortByDesc('updated_at')->take(7);

        return view('dashboard.pages.main', compact(
            'agentMetrics',
            'dashboardMetrics',
            'incomingOrders',
            'recentActivity',
            'salesTrend',
            'salesTrendMax',
            'statusBreakdown',
            'topMerchants'
        ));
    }

    private function moneyValue($value): float
    {
        return (float) str_replace(',', '', (string) $value);
    }

    public function orders()
    {
        return view('dashboard.pages.orders.index');
    }

    public function bookings()
    {
        return view('dashboard.pages.bookings.index');
    }

    public function reportOrder() {
    	return view('dashboard.pages.report.orders');	
    }

    public function memberList() {
    	return view('dashboard.pages.user.member');		
    }

    public function merchantlist() {
    	return view('dashboard.pages.user.merchant');		
    }

}
