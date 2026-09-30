<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\Http\Controllers\Controller;
use App\LibraryStatus;
use App\Model\Bookings\BookingStatus;
use App\Partners;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AgentCommissionReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'agent_id' => ['nullable', 'integer', 'exists:agents,id'],
            'restaurant_id' => ['nullable', 'integer', 'exists:partners,id'],
            'status' => ['nullable', Rule::in([
                AgentCommission::STATUS_PENDING,
                AgentCommission::STATUS_APPROVED,
                AgentCommission::STATUS_PAID,
                AgentCommission::STATUS_REVERSED,
            ])],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $from = Carbon::createFromFormat(
            'Y-m-d',
            $validated['from'] ?? now()->startOfMonth()->toDateString(),
        )->startOfDay();
        $to = Carbon::createFromFormat(
            'Y-m-d',
            $validated['to'] ?? now()->toDateString(),
        )->endOfDay();
        
        $selectedAgentId = isset($validated['agent_id']) ? (int) $validated['agent_id'] : null;
        $selectedRestaurantId = isset($validated['restaurant_id']) ? (int) $validated['restaurant_id'] : null;
        $selectedStatus = $validated['status'] ?? null;

        $baseQuery = AgentCommission::query()
            ->whereBetween('qualified_at', [$from, $to])
            // ->whereHas('order', fn (Builder $query) => $query->where(function (Builder $statusQuery) {
            //     $statusQuery
            //        ->whereIn('order_status_id', LibraryStatus::COMPLETED_STATUSES);
            // }))
            ->when($selectedAgentId, fn ($query) => $query->where('agent_id', $selectedAgentId))
            ->when($selectedRestaurantId, fn ($query) => $query->where('restaurant_id', $selectedRestaurantId))
            ->when($selectedStatus, fn ($query) => $query->where('status', $selectedStatus));

        $commissionCount = (clone $baseQuery)->count();
        $earnedQuery = (clone $baseQuery)->where('status', '!=', AgentCommission::STATUS_REVERSED);
        $reversedQuery = (clone $baseQuery)->where('status', AgentCommission::STATUS_REVERSED);

        $commissions = $baseQuery
            ->with([
                'agent:id,name,email',
                'restaurant:id,restaurant_name,agent_id,percentage',
                'order:id,cart_id',
                'order.cart:id,order_no',
                'order.cart.details:id,cart_id,qty,price_comm_total,variance_total_comm_total',
            ])
            ->latest('qualified_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        $commissions->getCollection()->each(function (AgentCommission $commission) {
            $cart = $commission->order?->cart;

            if (! $cart || $cart->details->isEmpty()) {
                return;
            }

            $pahatudCommission = round((float) $cart->cartItemAmounts()['total_comm'], 2);
            $commission->setAttribute('report_pahatud_commission_amount', $pahatudCommission);
            $commission->setAttribute(
                'report_commission_amount',
                round($pahatudCommission * ((float) $commission->commission_percentage / 100), 2),
            );
        });

        $agents = Agent::query()->orderBy('name')->get(['id', 'name']);
        $restaurants = Partners::query()
            ->whereNotNull('agent_id')
            ->when($selectedAgentId, fn ($query) => $query->where('agent_id', $selectedAgentId))
            ->orderBy('restaurant_name')
            ->get(['id', 'restaurant_name', 'agent_id']);

        return view('dashboard.pages.report.agent-commissions', [
            'commissions' => $commissions,
            'agents' => $agents,
            'restaurants' => $restaurants,
            'selectedAgentId' => $selectedAgentId,
            'selectedRestaurantId' => $selectedRestaurantId,
            'selectedStatus' => $selectedStatus,
            'from' => $from,
            'to' => $to,
            'metrics' => [
                'count' => $commissionCount,
                'subtotal' => (float) (clone $earnedQuery)->sum(DB::raw('COALESCE(subtotal_amount, order_amount)')),
                'commission' => $this->calculatedCommissionTotal($earnedQuery),
                'reversed' => $this->calculatedCommissionTotal($reversedQuery),
            ],
        ]);
    }

    private function calculatedCommissionTotal(Builder $query): float
    {
        $calculated = $query
            ->leftJoin('order as report_orders', 'report_orders.id', '=', 'agent_commissions.order_id')
            ->leftJoin('cart_details as report_items', 'report_items.cart_id', '=', 'report_orders.cart_id')
            ->selectRaw('agent_commissions.id')
            ->selectRaw(
                'CASE WHEN COUNT(report_items.id) > 0'
                .' THEN ROUND(COALESCE(SUM(report_items.qty * (report_items.price_comm_total + report_items.variance_total_comm_total)), 0)'
                .' * agent_commissions.commission_percentage / 100, 2)'
                .' ELSE agent_commissions.commission_amount END as calculated_commission',
            )
            ->groupBy(
                'agent_commissions.id',
                'agent_commissions.commission_percentage',
                'agent_commissions.commission_amount',
            );

        return (float) DB::query()
            ->fromSub($calculated, 'calculated_agent_commissions')
            ->sum('calculated_commission');
    }
}
