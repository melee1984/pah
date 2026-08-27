<?php

namespace App\Http\Controllers;

use App\RestaurantInvitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class RestaurantInvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = $this->validInvitation($token);
        $invitation->load(['restaurant:id,restaurant_name,mobile', 'user']);

        return view('merchant.pages.invitation', compact('invitation', 'token'));
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:75'],
            'lastname' => ['required', 'string', 'max:75'],
            'mobile' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::transaction(function () use ($token, $validated) {
            $invitation = RestaurantInvitation::query()
                ->where('token_hash', hash('sha256', $token))
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($invitation->isValid(), 410, 'This invitation has expired or has already been used.');

            $user = $invitation->user()->firstOrFail();
            $attributes = ['password' => Hash::make($validated['password'])];

            if (Schema::hasColumn('users', 'name')) {
                $attributes['name'] = trim($validated['firstname'].' '.$validated['lastname']);
            }
            if (Schema::hasColumn('users', 'firstname')) {
                $attributes['firstname'] = $validated['firstname'];
            }
            if (Schema::hasColumn('users', 'lastname')) {
                $attributes['lastname'] = $validated['lastname'];
            }
            if (Schema::hasColumn('users', 'mobile')) {
                $attributes['mobile'] = $validated['mobile'];
            }
            if (Schema::hasColumn('users', 'email_verified_at')) {
                $attributes['email_verified_at'] = now();
            }

            $user->forceFill($attributes)->save();
            $invitation->restaurant()->update(['mobile' => $validated['mobile']]);
            $invitation->update(['accepted_at' => now()]);

            return $user;
        });

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('merchant.status')
            ->with('success', 'Your Pahatud merchant account is ready. The restaurant remains under review until activation.');
    }

    private function validInvitation(string $token): RestaurantInvitation
    {
        $invitation = RestaurantInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->firstOrFail();

        abort_unless($invitation->isValid(), 410, 'This invitation has expired or has already been used.');

        return $invitation;
    }
}
