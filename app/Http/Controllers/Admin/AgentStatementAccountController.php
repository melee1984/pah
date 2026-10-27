<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\AgentStatementAccount;
use App\Http\Controllers\Controller;
use App\Services\AdminCityScope;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AgentStatementAccountController extends Controller
{
    public function __construct(private readonly AdminCityScope $cityScope) {}

    public function index(Request $request): View
    {
        $filters = $request->validate([
            'agent_id' => ['nullable', 'integer', Rule::exists('agents', 'id')],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $selectedAgentId = isset($filters['agent_id']) ? (int) $filters['agent_id'] : null;
        $from = isset($filters['from']) ? CarbonImmutable::parse($filters['from'])->startOfDay() : null;
        $to = isset($filters['to']) ? CarbonImmutable::parse($filters['to'])->endOfDay() : null;

        $commissionsQuery = AgentCommission::query()
            ->with([
                'agent:id,name,email',
                'restaurant:id,restaurant_name,agent_id,percentage',
                'order:id,cart_id',
                'order.cart:id,order_no',
            ])
            ->where('status', '!=', AgentCommission::STATUS_REVERSED)
            ->whereDoesntHave('statementItem')
            ->when($selectedAgentId, fn ($query) => $query->where('agent_id', $selectedAgentId))
            ->when($from, fn ($query) => $query->whereBetween('qualified_at', [$from, $to]))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('order_id', $search)
                        ->orWhereHas('order.cart', fn ($cart) => $cart->where('order_no', 'like', '%'.$search.'%'))
                        ->orWhereHas('restaurant', fn ($restaurant) => $restaurant->where('restaurant_name', 'like', '%'.$search.'%'));
                });
            })
            ->latest('qualified_at');

        if ($this->cityScope->restricted()) {
            $commissionsQuery->whereHas('agent', fn ($query) => $this->cityScope->agents($query));
        }

        $commissions = $commissionsQuery->paginate(50)->withQueryString();
        $agents = $this->cityScope->agents(Agent::query())->orderBy('name')->get(['id', 'name']);
        $statements = AgentStatementAccount::query()
            ->with('agent:id,name,email')
            ->when($this->cityScope->restricted(), fn ($query) => $query->whereHas('agent', fn ($agent) => $this->cityScope->agents($agent)))
            ->latest()
            ->paginate(15, ['*'], 'statements_page')
            ->withQueryString();

        return view('dashboard.pages.agent-statements.index', compact(
            'agents',
            'commissions',
            'filters',
            'selectedAgentId',
            'statements',
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'agent_id' => ['required', 'integer', Rule::exists('agents', 'id')],
            'commission_ids' => ['required', 'array', 'min:1'],
            'commission_ids.*' => ['integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $agent = Agent::query()->findOrFail($validated['agent_id']);
        $this->cityScope->authorizeAgent($agent);

        try {
            $statement = DB::transaction(function () use ($validated) {
                $commissions = AgentCommission::query()
                    ->with(['restaurant:id,restaurant_name,percentage', 'order:id,cart_id', 'order.cart:id,order_no'])
                    ->whereIn('id', $validated['commission_ids'])
                    ->where('agent_id', $validated['agent_id'])
                    ->where('status', '!=', AgentCommission::STATUS_REVERSED)
                    ->lockForUpdate()
                    ->get();

                if ($commissions->count() !== count($validated['commission_ids'])) {
                    throw ValidationException::withMessages([
                        'commission_ids' => 'Every selected transaction must belong to this agent and must not be reversed.',
                    ]);
                }

                if (DB::table('agent_statement_account_items')->whereIn('agent_commission_id', $commissions->pluck('id'))->exists()) {
                    throw ValidationException::withMessages([
                        'commission_ids' => 'One or more selected commissions are already on an agent statement. Refresh and try again.',
                    ]);
                }

                $lines = $commissions->map(function (AgentCommission $commission) {
                    $orderAmount = (float) ($commission->total_amount ?? $commission->order_amount);
                    $pahatudPercentage = (float) ($commission->pahatud_commission_percentage
                        ?? $commission->restaurant?->percentage
                        ?? config('agent.pahatud_commission_percentage'));
                    $pahatudAmount = (float) ($commission->pahatud_commission_amount
                        ?? round((float) ($commission->subtotal_amount ?? $commission->order_amount) * ($pahatudPercentage / 100), 2));

                    return [
                        'agent_commission_id' => $commission->id,
                        'order_id' => $commission->order_id,
                        'restaurant_id' => $commission->restaurant_id,
                        'restaurant_name' => $commission->restaurant?->restaurant_name,
                        'order_number' => $commission->order?->cart?->order_no,
                        'qualified_at' => $commission->qualified_at,
                        'order_amount' => $orderAmount,
                        'pahatud_commission_percentage' => $pahatudPercentage,
                        'pahatud_commission_amount' => $pahatudAmount,
                        'agent_commission_percentage' => $commission->commission_percentage,
                        'agent_commission_amount' => $commission->commission_amount,
                        'commission_status' => $commission->status,
                    ];
                });

                $statement = AgentStatementAccount::query()->create([
                    'reference' => 'ASOA-TMP-'.str()->random(20),
                    'agent_id' => $validated['agent_id'],
                    'created_by' => Auth::id(),
                    'status' => AgentStatementAccount::STATUS_DRAFT,
                    'transaction_count' => $lines->count(),
                    'period_start' => $lines->min('qualified_at'),
                    'period_end' => $lines->max('qualified_at'),
                    'pahatud_commission_amount' => $lines->sum('pahatud_commission_amount'),
                    'agent_commission_amount' => $lines->sum('agent_commission_amount'),
                    'notes' => $validated['notes'] ?? null,
                    'issued_at' => null,
                ]);

                $statement->update([
                    'reference' => 'ASOA-'.now()->format('Ym').'-'.str_pad((string) $statement->id, 6, '0', STR_PAD_LEFT),
                ]);
                $statement->items()->createMany($lines->all());

                return $statement;
            });
        } catch (QueryException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505'], true)) {
                throw ValidationException::withMessages([
                    'commission_ids' => 'One or more selected commissions were just added to another statement. Refresh and try again.',
                ]);
            }

            throw $exception;
        }

        return redirect()->route('dashboard.agent-statements.show', $statement)
            ->with('success', 'Draft '.$statement->reference.' was generated. Review it before publishing it to the agent.');
    }

    public function show(AgentStatementAccount $statement): View
    {
        $this->cityScope->authorizeAgent($statement->agent);
        $statement->load(['agent', 'items', 'creator']);

        return view('dashboard.pages.agent-statements.show', compact('statement'));
    }

    public function publish(AgentStatementAccount $statement)
    {
        $this->cityScope->authorizeAgent($statement->agent);

        DB::transaction(function () use ($statement) {
            $lockedStatement = AgentStatementAccount::query()->lockForUpdate()->findOrFail($statement->id);

            if (! $lockedStatement->isDraft()) {
                throw ValidationException::withMessages([
                    'statement' => 'Only a draft agent statement can be published.',
                ]);
            }

            $lockedStatement->update([
                'status' => AgentStatementAccount::STATUS_PUBLISHED,
                'issued_at' => now(),
            ]);
        });

        return redirect()->route('dashboard.agent-statements.show', $statement)
            ->with('success', 'Statement '.$statement->reference.' was published and is now available in the agent dashboard.');
    }

    public function destroy(AgentStatementAccount $statement)
    {
        $this->cityScope->authorizeAgent($statement->agent);

        DB::transaction(function () use ($statement) {
            $lockedStatement = AgentStatementAccount::query()->lockForUpdate()->findOrFail($statement->id);

            if (! $lockedStatement->isDraft()) {
                throw ValidationException::withMessages([
                    'statement' => 'Published agent statements cannot be deleted.',
                ]);
            }

            $lockedStatement->delete();
        });

        return redirect()->route('dashboard.agent-statements.index')
            ->with('success', 'The draft agent statement was deleted. Its commissions are available for statement processing again.');
    }
}
