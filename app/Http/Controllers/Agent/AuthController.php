<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('agent')->check()) {
            return redirect()->route(Auth::guard('agent')->user()->must_change_password
                ? 'agent.password.edit'
                : 'agent.dashboard');
        }

        return view('agent.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $credentials['active'] = true;

        if (! Auth::guard('agent')->attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withErrors(['email' => 'The email or password is incorrect, or this account is inactive.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->user('agent')->forceFill(['last_login_at' => now()])->save();

        if ($request->user('agent')->must_change_password) {
            return redirect()->route('agent.password.edit');
        }

        return redirect()->intended(route('agent.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('agent')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('agent.login');
    }
}
