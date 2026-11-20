@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Access control</span><h1>User roles</h1><p>See who can access the data dashboard and assign one dashboard role to each user.</p></div>
            </div>
        </div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

        <div class="row mb-4">
            @foreach ([
                ['label' => 'Dashboard access', 'value' => $dashboardAccessCount, 'icon' => 'fa-chart-line'],
                ['label' => 'Super admins', 'value' => $roleCounts['super'], 'icon' => 'fa-user-shield'],
                ['label' => 'Admins', 'value' => $roleCounts['admin'], 'icon' => 'fa-user-cog'],
                ['label' => 'City managers', 'value' => $roleCounts['manager'], 'icon' => 'fa-map-marker-alt'],
            ] as $metric)
            <div class="col-lg-3 col-sm-6 mb-3 mb-lg-0">
                <div class="card admin-card h-100"><div class="card-body d-flex align-items-center">
                    <span class="admin-stat-icon mr-3"><i class="fas {{ $metric['icon'] }}"></i></span>
                    <div><small class="text-muted text-uppercase">{{ $metric['label'] }}</small><h2 class="mb-0">{{ number_format($metric['value']) }}</h2></div>
                </div></div>
            </div>
            @endforeach
        </div>

        <div class="card admin-card">
            <div class="admin-card-header">
                <div><h2>Users and access</h2><p>{{ number_format($users->total()) }} {{ Str::plural('account', $users->total()) }} found</p></div>
            </div>
            <div class="card-body border-bottom">
                <form method="GET" action="{{ route('dashboard.user-roles.index') }}">
                    <div class="row">
                        <div class="form-group col-lg-5 mb-lg-0"><label for="role_search">Search users</label><input class="form-control" id="role_search" name="search" value="{{ $search }}" placeholder="Name, email, or mobile"></div>
                        <div class="form-group col-lg-3 mb-lg-0"><label for="role_filter">Role</label><select class="form-control" id="role_filter" name="role"><option value="">All roles</option>@foreach (['super' => 'Super admin', 'admin' => 'Admin', 'manager' => 'City manager'] as $value => $label)<option value="{{ $value }}" @selected($roleFilter === $value)>{{ $label }}</option>@endforeach</select></div>
                        <div class="form-group col-lg-2 mb-lg-0"><label for="access_filter">Access</label><select class="form-control" id="access_filter" name="access"><option value="">All users</option><option value="dashboard" @selected($accessFilter === 'dashboard')>Has dashboard access</option><option value="none" @selected($accessFilter === 'none')>No dashboard access</option></select></div>
                        <div class="form-group col-lg-2 d-flex align-items-end mb-0"><button class="btn admin-btn-primary mr-2" type="submit">Filter</button><a class="btn admin-btn-secondary" href="{{ route('dashboard.user-roles.index') }}">Clear</a></div>
                    </div>
                </form>
            </div>

            @if ($users->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-users-cog"></i></span><h3>No users found</h3><p>Try changing the search or access filters.</p></div>
            @else
                <div class="table-responsive"><table class="table admin-table">
                    <thead><tr><th>User</th><th>All roles</th><th>Dashboard access</th><th>City</th><th>Update access</th></tr></thead>
                    <tbody>@foreach ($users as $user)
                    @php($dashboardRole = $user->roles->pluck('name')->first(fn ($role) => in_array($role, ['super', 'admin', 'manager'], true)))
                    @php($canEditUser = ! auth()->user()->is($user) && (auth()->user()->hasRole('super') || $dashboardRole !== 'super'))
                    <tr>
                        <td><strong>{{ trim($user->full_name) ?: 'Unnamed user' }}</strong><small>{{ $user->email }}@if ($user->mobile) · {{ $user->mobile }}@endif</small></td>
                        <td>@forelse ($user->roles as $role)<span class="badge badge-light mr-1">{{ Str::headline($role->name) }}</span>@empty<span class="text-muted">No roles</span>@endforelse</td>
                        <td>@if ($dashboardRole)<span class="admin-status admin-status-active">{{ $dashboardRole === 'super' ? 'Super admin' : Str::headline($dashboardRole) }}</span>@else<span class="admin-status">No access</span>@endif</td>
                        <td>{{ $dashboardRole === 'manager' ? ($user->managed_city ?: 'Not assigned') : '—' }}</td>
                        <td>
                            @if (! $canEditUser)
                                <small class="text-muted"><i class="fas fa-lock mr-1"></i>{{ auth()->user()->is($user) ? 'Your own access cannot be changed here.' : 'Only a super admin can change this account.' }}</small>
                            @else
                                <form method="POST" action="{{ route('dashboard.user-roles.update', $user) }}" class="d-flex flex-wrap" style="gap: 8px">@csrf @method('PUT')
                                    <select class="form-control form-control-sm" name="dashboard_role" style="width: 145px" onchange="this.form.querySelector('[name=managed_city]').style.display = this.value === 'manager' ? 'block' : 'none'">
                                        <option value="">No access</option>@if (auth()->user()->hasRole('super'))<option value="super" @selected($dashboardRole === 'super')>Super admin</option>@endif<option value="admin" @selected($dashboardRole === 'admin')>Admin</option><option value="manager" @selected($dashboardRole === 'manager')>City manager</option>
                                    </select>
                                    <input class="form-control form-control-sm" name="managed_city" value="{{ $user->managed_city }}" placeholder="Assigned city" maxlength="120" style="width: 145px; {{ $dashboardRole === 'manager' ? '' : 'display: none' }}">
                                    <button class="btn admin-btn-secondary btn-sm" type="submit" onclick="return confirm('Update dashboard access for this user?')">Save</button>
                                </form>
                            @endif
                        </td>
                    </tr>@endforeach</tbody>
                </table></div>
                <div class="admin-pagination">{{ $users->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div></section>
</div>
@endsection
