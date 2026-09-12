<?php

namespace App\Http\Controllers\Admin;

use App\Agent;
use App\AgentCommission;
use App\Http\Controllers\Controller;
use App\Mail\AgentTemporaryPasswordMail;
use App\Mail\AgentApprovedMail;
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
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');
        $agents = Agent::query()
            ->withCount('restaurants')
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

        $metrics = [
            'total' => Agent::query()->count(),
            'active' => Agent::query()->where('active', true)->count(),
            'password_pending' => Agent::query()->where('must_change_password', true)->count(),
            'commission' => (float) AgentCommission::query()->earned()->sum('commission_amount'),
        ];

        return view('dashboard.pages.agents.index', compact('agents', 'metrics', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('agents', 'email')],
            'mobile' => ['nullable', 'string', 'max:30'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $temporaryPassword = Str::password(12, symbols: false);
                $agent = Agent::query()->create([
                    ...$validated,
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

    public function approve(Agent $agent): RedirectResponse
    {
        if ($agent->active) {
            return back()->with('success', $agent->name.' already has an active Agent Portal account.');
        }

        $agent->update(['active' => true]);

        try {
            Mail::to($agent->email)->send(new AgentApprovedMail($agent));
        } catch (Throwable $exception) {
            Log::error('Agent was approved but the approval email could not be delivered.', [
                'agent_id' => $agent->id,
                'email' => $agent->email,
                'exception' => $exception->getMessage(),
            ]);

            return back()->withErrors(['email' => 'The account was approved, but the approval email could not be delivered.']);
        }

        return back()->with('success', $agent->name.' was approved and notified by email.');
    }
}
