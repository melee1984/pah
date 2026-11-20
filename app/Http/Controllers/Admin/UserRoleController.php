<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    private const DASHBOARD_ROLES = ['super', 'admin', 'manager'];

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'role' => ['nullable', Rule::in(self::DASHBOARD_ROLES)],
            'access' => ['nullable', Rule::in(['dashboard', 'none'])],
        ]);

        $search = trim($validated['search'] ?? '');
        $roleFilter = $validated['role'] ?? '';
        $accessFilter = $validated['access'] ?? '';

        $users = User::query()
            ->with('roles:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('mobile', 'like', "%{$search}%");
                });
            })
            ->when($roleFilter !== '', fn ($query) => $query->whereHas(
                'roles',
                fn ($query) => $query->where('name', $roleFilter)->where('guard_name', 'web')
            ))
            ->when($accessFilter === 'dashboard', fn ($query) => $this->withDashboardAccess($query))
            ->when($accessFilter === 'none', fn ($query) => $query->whereDoesntHave(
                'roles',
                fn ($query) => $query->whereIn('name', self::DASHBOARD_ROLES)->where('guard_name', 'web')
            ))
            ->orderBy('email')
            ->paginate(25)
            ->withQueryString();

        $roleCounts = collect(self::DASHBOARD_ROLES)->mapWithKeys(fn ($role) => [
            $role => User::query()->whereHas(
                'roles',
                fn ($query) => $query->where('name', $role)->where('guard_name', 'web')
            )->count(),
        ]);
        $dashboardAccessCount = $this->withDashboardAccess(User::query())->count();

        return view('dashboard.pages.user-roles.index', compact(
            'accessFilter',
            'dashboardAccessCount',
            'roleCounts',
            'roleFilter',
            'search',
            'users',
        ));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $assignableRoles = $request->user()->hasRole('super')
            ? self::DASHBOARD_ROLES
            : ['admin', 'manager'];

        $validated = $request->validate([
            'dashboard_role' => ['nullable', Rule::in($assignableRoles)],
            'managed_city' => ['nullable', 'required_if:dashboard_role,manager', 'string', 'max:120'],
        ]);

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'dashboard_role' => 'You cannot change your own dashboard access.',
            ]);
        }

        $newRole = $validated['dashboard_role'] ?? null;
        if ($user->hasRole('super') && ! $request->user()->hasRole('super')) {
            throw ValidationException::withMessages([
                'dashboard_role' => 'Only a super admin can change another super admin account.',
            ]);
        }

        if ($user->hasRole('super') && $newRole !== 'super' && $this->superAdminCount() <= 1) {
            throw ValidationException::withMessages([
                'dashboard_role' => 'The last super admin cannot be removed or reassigned.',
            ]);
        }

        DB::transaction(function () use ($user, $newRole, $validated) {
            $assignedDashboardRoles = $user->roles
                ->where('guard_name', 'web')
                ->pluck('name')
                ->intersect(self::DASHBOARD_ROLES);

            foreach ($assignedDashboardRoles as $role) {
                $user->removeRole($role);
            }

            if ($newRole) {
                Role::findOrCreate($newRole, 'web');
                $user->assignRole($newRole);
            }

            $user->forceFill([
                'managed_city' => $newRole === 'manager' ? trim($validated['managed_city']) : null,
            ])->save();
        });

        $message = $newRole
            ? $user->email.' now has '.str($newRole)->headline()->lower().' dashboard access.'
            : 'Dashboard access was removed from '.$user->email.'.';

        return back()->with('success', $message);
    }

    private function withDashboardAccess($query)
    {
        return $query->whereHas('roles', fn ($query) => $query
            ->whereIn('name', self::DASHBOARD_ROLES)
            ->where('guard_name', 'web'));
    }

    private function superAdminCount(): int
    {
        return User::query()->whereHas(
            'roles',
            fn ($query) => $query->where('name', 'super')->where('guard_name', 'web')
        )->count();
    }
}
