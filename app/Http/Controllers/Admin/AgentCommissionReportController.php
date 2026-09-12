<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\Http\Controllers\Controller;
use App\Partners;
use Carbon\Carbon;
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
            ])
            ->latest('qualified_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

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
                'commission' => (float) (clone $earnedQuery)->sum('commission_amount'),
                'reversed' => (float) $reversedQuery->sum('commission_amount'),
            ],
        ]);
    }
}
