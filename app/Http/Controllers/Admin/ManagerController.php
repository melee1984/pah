<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Partners;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ManagerController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $search = trim($validated['search'] ?? '');

        $managers = User::role('manager')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('managed_city', 'like', "%{$search}%");
                });
            })
            ->orderBy('managed_city')
            ->orderBy('email')
            ->paginate(25)
            ->withQueryString();

        $cities = Partners::query()
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->distinct()
            ->orderBy('city')
            ->pluck('city');

        return view('dashboard.pages.managers.index', compact('cities', 'managers', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255', Rule::exists('users', 'email')],
            'managed_city' => ['required', 'string', 'max:120'],
        ]);

        $user = User::query()->where('email', $validated['email'])->firstOrFail();
        $this->ensureAssignable($request, $user);

        Role::findOrCreate('manager', 'web');
        $user->forceFill(['managed_city' => trim($validated['managed_city'])])->save();
        $user->assignRole('manager');

        return back()->with('success', $user->email.' can now manage '.trim($validated['managed_city']).'.');
    }

    public function update(Request $request, User $manager): RedirectResponse
    {
        abort_unless($manager->hasRole('manager'), 404);
        $validated = $request->validate([
            'managed_city' => ['required', 'string', 'max:120'],
        ]);

        $manager->forceFill(['managed_city' => trim($validated['managed_city'])])->save();

        return back()->with('success', $manager->email.' is now assigned to '.trim($validated['managed_city']).'.');
    }

    public function destroy(Request $request, User $manager): RedirectResponse
    {
        abort_unless($manager->hasRole('manager'), 404);
        if ($request->user()->is($manager)) {
            throw ValidationException::withMessages([
                'manager' => 'You cannot remove your own manager access.',
            ]);
        }

        $manager->removeRole('manager');
        $manager->forceFill(['managed_city' => null])->save();

        return back()->with('success', 'Manager access was removed from '.$manager->email.'.');
    }

    private function ensureAssignable(Request $request, User $user): void
    {
        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'email' => 'You cannot change your own administrator role here.',
            ]);
        }

        if ($user->hasAnyRole(['admin', 'super'])) {
            throw ValidationException::withMessages([
                'email' => 'Administrator and super-user accounts cannot be converted to city managers.',
            ]);
        }
    }
}
