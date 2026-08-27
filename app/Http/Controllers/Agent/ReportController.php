<?php

namespace App\Http\Controllers\Agent;

use App\AgentCommission;
use App\Http\Controllers\Controller;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'status' => ['nullable', Rule::in([
                AgentCommission::STATUS_PENDING,
                AgentCommission::STATUS_APPROVED,
                AgentCommission::STATUS_PAID,
                AgentCommission::STATUS_REVERSED,
            ])],
        ]);

        $from = CarbonImmutable::parse($validated['from'] ?? now()->startOfMonth()->toDateString())->startOfDay();
        $to = CarbonImmutable::parse($validated['to'] ?? now()->toDateString())->endOfDay();

        $query = $request->user('agent')
            ->commissions()
            ->with(['restaurant:id,restaurant_name', 'order:id,order_no'])
            ->whereBetween('qualified_at', [$from, $to])
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $totals = [
            'orders' => (clone $query)->count(),
            'order_value' => (float) (clone $query)->sum('order_amount'),
            'commission' => (float) (clone $query)->where('status', '!=', AgentCommission::STATUS_REVERSED)->sum('commission_amount'),
        ];

        $commissions = $query->latest('qualified_at')->paginate(25)->withQueryString();

        return view('agent.reports.index', compact('commissions', 'totals', 'from', 'to'));
    }
}
