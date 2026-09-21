<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user('agent')->must_change_password) {
            return redirect()->route('agent.dashboard');
        }

        return view('agent.auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:agent'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $request->user('agent')->update([
            'password' => $validated['password'],
            'must_change_password' => false,
            'password_changed_at' => now(),
        ]);
        $request->session()->regenerate();

        return redirect()
            ->route('agent.dashboard')
            ->with('success', 'Your password has been changed successfully.');
    }
}
