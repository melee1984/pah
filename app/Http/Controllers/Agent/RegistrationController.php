<?php

namespace App\Http\Controllers\Agent;

use App\Agent;
use App\Http\Controllers\Controller;
use App\Mail\AgentRegistrationReceivedMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('agent.auth.register', [
            'commissionPercentage' => (float) config('agent.default_commission_percentage'),
            'pahatudCommissionPercentage' => (float) config('agent.pahatud_commission_percentage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('agents', 'email')],
            'mobile' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $agent = Agent::query()->create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'mobile' => $validated['mobile'],
                    'password' => $validated['password'],
                    'commission_percentage' => config('agent.default_commission_percentage'),
                    'active' => false,
                    'must_change_password' => false,
                    'password_changed_at' => now(),
                ]);

                Mail::to($agent->email)->send(new AgentRegistrationReceivedMail($agent));
            });
        } catch (Throwable $exception) {
            Log::error('Public agent registration could not be completed.', [
                'email' => $validated['email'],
                'exception' => $exception->getMessage(),
            ]);

            return back()->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['email' => 'We could not complete your registration or send the confirmation email. Please try again.']);
        }

        return redirect()->route('agent.register.success')
            ->with('agent_registration_completed', true)
            ->with('agent_registration_email', $validated['email']);
    }

    public function success(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('agent_registration_completed')) {
            return redirect()->route('agent.register');
        }

        return view('agent.auth.registration-success', [
            'email' => $request->session()->get('agent_registration_email'),
        ]);
    }
}
