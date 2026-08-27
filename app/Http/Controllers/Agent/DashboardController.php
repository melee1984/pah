<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Model\Orders\Orders;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $agent = $request->user('agent');
        $restaurantIds = $agent->restaurants()->pluck('id');
        $earned = $agent->commissions()->earned();

        $metrics = [
            'restaurants' => $restaurantIds->count(),
            'orders' => $restaurantIds->isEmpty()
                ? 0
                : Orders::query()->whereIn('partner_id', $restaurantIds)->whereNotNull('submitted_at')->count(),
            'sales' => (float) (clone $earned)->sum('order_amount'),
            'commission' => (float) (clone $earned)->sum('commission_amount'),
            'month_commission' => (float) (clone $earned)
                ->whereBetween('qualified_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('commission_amount'),
        ];

        $recentCommissions = $agent->commissions()
            ->with(['restaurant:id,restaurant_name', 'order:id,order_no'])
            ->latest('qualified_at')
            ->limit(8)
            ->get();

        return view('agent.dashboard', compact('agent', 'metrics', 'recentCommissions'));
    }
}
