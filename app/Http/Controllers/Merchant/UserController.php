<?php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\MerchantBranchUser;
use App\Partners;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $merchant = $this->ownedMerchant();

        return view('merchant.pages.users.index', [
            'branches' => $merchant->locations()->orderBy('address_1')->orderBy('city')->get(),
            'merchantUsers' => MerchantBranchUser::query()
                ->with(['user', 'branch'])
                ->where('partner_id', $merchant->id)
                ->orderByDesc('active')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $merchant = $this->ownedMerchant();
        $validated = $request->validate([
            'firstname' => ['required', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'mobile' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'partner_location_id' => [
                'required',
                'integer',
                Rule::exists('partner_location', 'id')->where('partner_id', $merchant->id),
            ],
        ]);

        DB::transaction(function () use ($validated, $merchant) {
            $attributes = [
                'firstname' => $validated['firstname'],
                'lastname' => $validated['lastname'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'password' => Hash::make($validated['password']),
                'api_token' => null,
            ];

            if (Schema::hasColumn('users', 'name')) {
                $attributes['name'] = trim($validated['firstname'].' '.$validated['lastname']);
            }

            $user = new User;
            $user->forceFill($attributes)->save();
            $user->assignRole('partner');

            MerchantBranchUser::query()->create([
                'user_id' => $user->id,
                'partner_id' => $merchant->id,
                'partner_location_id' => $validated['partner_location_id'],
                'created_by_user_id' => Auth::id(),
                'active' => true,
            ]);
        });

        return redirect()->route('merchant.dashboard.users.index')
            ->with('success', 'Branch user created successfully.');
    }

    public function update(MerchantBranchUser $merchantUser, Request $request)
    {
        $merchant = $this->ownedMerchant();
        abort_unless((int) $merchantUser->partner_id === (int) $merchant->id, 404);

        $validated = $request->validate([
            'partner_location_id' => [
                'required',
                'integer',
                Rule::exists('partner_location', 'id')->where('partner_id', $merchant->id),
            ],
            'active' => ['required', 'boolean'],
        ]);

        $merchantUser->update([
            'partner_location_id' => $validated['partner_location_id'],
            'active' => $validated['active'],
        ]);

        if (! $merchantUser->active) {
            $merchantUser->user()->update(['api_token' => null]);
        }

        return redirect()->route('merchant.dashboard.users.index')
            ->with('success', 'Branch user assignment updated successfully.');
    }

    private function ownedMerchant(): Partners
    {
        return Partners::query()->where('user_id', Auth::id())->firstOrFail();
    }
}
