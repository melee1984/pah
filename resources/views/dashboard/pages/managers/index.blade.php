@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Access control</span><h1>Manager access</h1><p>Assign an existing user account to one city. Administrators and super users remain unrestricted.</p></div>
            </div>
        </div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

        <div class="card admin-card mb-4">
            <div class="admin-card-header"><div><h2>Assign a manager</h2><p>The account must already exist. Its existing non-administrator role is preserved.</p></div></div>
            <form method="POST" action="{{ route('dashboard.managers.store') }}">
                @csrf
                <div class="card-body"><div class="row">
                    <div class="form-group col-md-6"><label for="manager_email">User email</label><input class="form-control" id="manager_email" name="email" type="email" value="{{ old('email') }}" placeholder="manager@example.com" required></div>
                    <div class="form-group col-md-4"><label for="manager_city">Assigned city</label><input class="form-control" id="manager_city" name="managed_city" value="{{ old('managed_city') }}" list="managerCities" maxlength="120" placeholder="Samal" required></div>
                    <div class="form-group col-md-2 d-flex align-items-end"><button class="btn admin-btn-primary btn-block" type="submit"><i class="fas fa-user-shield mr-2"></i>Assign</button></div>
                </div></div>
            </form>
        </div>

        <datalist id="managerCities">@foreach ($cities as $city)<option value="{{ $city }}">@endforeach</datalist>

        <div class="card admin-card">
            <div class="admin-card-header">
                <div><h2>City managers</h2><p>{{ number_format($managers->total()) }} assigned {{ Str::plural('manager', $managers->total()) }}</p></div>
                <form class="admin-search" method="GET" action="{{ route('dashboard.managers.index') }}"><i class="fas fa-search"></i><input name="search" value="{{ $search }}" placeholder="Search manager or city" aria-label="Search managers">@if ($search !== '')<a href="{{ route('dashboard.managers.index') }}" aria-label="Clear search">&times;</a>@endif</form>
            </div>
            @if ($managers->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-user-shield"></i></span><h3>No managers found</h3><p>Assign an existing user account above.</p></div>
            @else
                <div class="table-responsive"><table class="table admin-table">
                    <thead><tr><th>Manager</th><th>Assigned city</th><th>Roles</th><th>Update city</th><th>Action</th></tr></thead>
                    <tbody>@foreach ($managers as $manager)<tr>
                        <td><strong>{{ trim($manager->full_name) ?: 'Unnamed user' }}</strong><small>{{ $manager->email }}</small></td>
                        <td><span class="admin-status admin-status-active">{{ $manager->managed_city ?: 'Not assigned' }}</span></td>
                        <td>{{ $manager->getRoleNames()->map(fn ($role) => Str::headline($role))->join(', ') }}</td>
                        <td><form class="d-flex" style="gap: 8px" method="POST" action="{{ route('dashboard.managers.update', $manager) }}">@csrf @method('PUT')<input class="form-control form-control-sm" name="managed_city" value="{{ $manager->managed_city }}" list="managerCities" maxlength="120" required><button class="btn admin-btn-secondary btn-sm" type="submit">Save</button></form></td>
                        <td><form method="POST" action="{{ route('dashboard.managers.destroy', $manager) }}">@csrf @method('DELETE')<button class="btn admin-btn-secondary btn-sm" type="submit" onclick="return confirm('Remove manager access from this account?')"><i class="fas fa-user-minus mr-1"></i>Remove access</button></form></td>
                    </tr>@endforeach</tbody>
                </table></div>
                <div class="admin-pagination">{{ $managers->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div></section>
</div>
@endsection
