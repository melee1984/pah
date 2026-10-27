<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\Http\Controllers\Controller;
use App\Mail\AgentApprovedMail;
use App\Mail\AgentDeclinedMail;
use App\Mail\AgentTemporaryPasswordMail;
use App\Services\AdminCityScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AgentController extends Controller
{
    public function __construct(private readonly AdminCityScope $cityScope) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');
        $agentQuery = Agent::query();
        $this->cityScope->agents($agentQuery);
        $restaurantScope = fn ($query) => $this->cityScope->partners($query);
        $agents = $agentQuery
            ->withCount(['restaurants' => $restaurantScope])
            ->withCount(['approvedRestaurants' => $restaurantScope])
            ->withSum(['commissions as commission_total' => fn ($query) => $query->earned()], 'commission_amount')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $metricAgents = fn () => $this->cityScope->agents(Agent::query());
        $commissionQuery = AgentCommission::query()->earned();
        if ($this->cityScope->restricted()) {
            $commissionQuery->whereHas('agent', fn ($query) => $this->cityScope->agents($query));
        }
        $metrics = [
            'total' => $metricAgents()->count(),
            'active' => $metricAgents()->where('active', true)->count(),
            'password_pending' => $metricAgents()->where('must_change_password', true)->count(),
            'commission' => (float) $commissionQuery->sum('commission_amount'),
        ];

        return view('dashboard.pages.agents.index', compact('agents', 'metrics', 'search'));
    }

    public function show(Agent $agent): View
    {
        $restaurantScope = fn ($query) => $this->cityScope->partners($query);
        $agent->loadCount(['restaurants' => $restaurantScope])
            ->loadCount(['approvedRestaurants' => $restaurantScope])
            ->loadSum(['commissions as commission_total' => fn ($query) => $query->earned()], 'commission_amount');

        $restaurantsQuery = $agent->restaurants();
        $this->cityScope->partners($restaurantsQuery);
        $restaurants = $restaurantsQuery->orderBy('restaurant_name')
            ->paginate(10);

        return view('dashboard.pages.agents.show', compact('agent', 'restaurants'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('agents', 'email')],
            'mobile' => ['nullable', 'string', 'max:30'],
            'city' => ['required', 'string', 'max:120'],
        ]);

        if ($this->cityScope->restricted()) {
            $validated['city'] = auth()->user()->managed_city;
        }

        try {
            DB::transaction(function () use ($validated) {
                $temporaryPassword = Str::password(12, symbols: false);
                $agent = Agent::query()->create([
                    ...$validated,
                    'commission_percentage' => config('agent.commission_tiers.0', 15),
                    'password' => $temporaryPassword,
                    'active' => true,
                    'must_change_password' => true,
                    'temporary_password_created_at' => now(),
                ]);

                Mail::to($agent->email)->send(new AgentTemporaryPasswordMail($agent, $temporaryPassword));
            });
        } catch (Throwable $exception) {
            Log::error('Agent account and temporary password email could not be created.', [
                'email' => $validated['email'],
                'exception' => $exception->getMessage(),
            ]);

            return back()
                ->withInput()
                ->withErrors(['email' => 'The agent was not created because the temporary password email could not be delivered. Check the mail configuration and try again.']);
        }

        return redirect()
            ->route('dashboard.agents.index')
            ->with('success', 'Agent account created. A temporary password was emailed to '.$validated['email'].'.');
    }

    public function approve(Request $request, Agent $agent): RedirectResponse
    {
        return $this->review($request, $agent, 'approved');
    }

    public function decline(Request $request, Agent $agent): RedirectResponse
    {
        return $this->review($request, $agent, 'declined');
    }

    private function review(Request $request, Agent $agent, string $decision): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
        ]);
        $message = trim($validated['message'] ?? '') ?: null;

        if ($agent->active || $agent->review_status !== null) {
            return back()->withErrors(['review' => 'This agent application has already been reviewed.']);
        }

        try {
            DB::transaction(function () use ($agent, $decision, $message) {
                $pendingAgent = Agent::query()->lockForUpdate()->findOrFail($agent->id);

                if ($pendingAgent->active || $pendingAgent->review_status !== null) {
                    throw new \LogicException('This agent application has already been reviewed.');
                }

                $pendingAgent->update([
                    'active' => $decision === 'approved',
                    'review_status' => $decision,
                    'review_message' => $message,
                    'reviewed_at' => now(),
                ]);

                $mail = $decision === 'approved'
                    ? new AgentApprovedMail($pendingAgent, $message)
                    : new AgentDeclinedMail($pendingAgent, $message);
                Mail::to($pendingAgent->email)->send($mail);
            });
        } catch (Throwable $exception) {
            Log::error('Agent application review could not be completed.', [
                'agent_id' => $agent->id,
                'email' => $agent->email,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['review' => 'The application could not be reviewed or the email could not be delivered. Please try again.']);
        }

        return back()->with('success', $agent->name.' was '.$decision.' and notified by email.');
    }
}
