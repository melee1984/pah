<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View|RedirectResponse
    {
        if (! $request->user('web')->must_change_password) {
            return redirect()->route('merchant.dashboard.index');
        }

        return view('merchant.pages.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password:web'],
            'password' => ['required', 'string', 'min:8', 'confirmed', 'different:current_password'],
        ]);

        $request->user('web')->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
            'password_changed_at' => now(),
        ])->save();
        $request->session()->regenerate();

        return redirect()
            ->route('merchant.dashboard.index')
            ->with('success', 'Your password has been changed successfully.');
    }
}
