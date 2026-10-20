@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
  <section class="content-header"><div class="container-fluid">
    <div class="admin-page-heading">
      <div><span class="admin-eyebrow">Access management</span><h1>Branch users</h1><p>Create a separate merchant login for each branch operator.</p></div>
      <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Branch users</li></ol>
    </div>
  </div></section>

  <section class="content"><div class="container-fluid">
    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @include('includes.error')

    <div class="merchant-dashboard-grid align-items-start">
      <div class="card admin-card">
        <div class="admin-card-header"><div><span class="admin-eyebrow">New login</span><h2>Add branch user</h2></div></div>
        <div class="card-body">
          @if ($branches->isEmpty())
            <div class="admin-empty-state"><span><i class="fas fa-map-marker-alt"></i></span><h3>Add a branch first</h3><p>A user cannot be created without a designated branch.</p><a class="btn admin-btn-primary mt-3" href="{{ route('merchant.dashboard.location') }}">Manage branches</a></div>
          @else
            <form class="merchant-settings-form" method="POST" action="{{ route('merchant.dashboard.users.store') }}">
              @csrf
              <div class="merchant-form-grid">
                <div class="form-group"><label for="branch-user-firstname">First name</label><input id="branch-user-firstname" class="form-control" name="firstname" value="{{ old('firstname') }}" required></div>
                <div class="form-group"><label for="branch-user-lastname">Last name</label><input id="branch-user-lastname" class="form-control" name="lastname" value="{{ old('lastname') }}" required></div>
                <div class="form-group merchant-form-span"><label for="branch-user-email">Email address</label><input id="branch-user-email" class="form-control" type="email" name="email" value="{{ old('email') }}" required></div>
                <div class="form-group merchant-form-span"><label for="branch-user-mobile">Mobile number</label><input id="branch-user-mobile" class="form-control" name="mobile" value="{{ old('mobile') }}" required></div>
                <div class="form-group merchant-form-span"><label for="branch-user-location">Designated branch</label><select id="branch-user-location" class="form-control" name="partner_location_id" required><option value="">Select a branch</option>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((int) old('partner_location_id') === (int) $branch->id)>{{ $branch->address_1 }}{{ $branch->city ? ', '.$branch->city : '' }}</option>@endforeach</select><small class="form-text text-muted">This login can access only this branch in the merchant mobile application.</small></div>
                <div class="form-group"><label for="branch-user-password">Temporary password</label><input id="branch-user-password" class="form-control" type="password" name="password" minlength="8" required></div>
                <div class="form-group"><label for="branch-user-password-confirmation">Confirm password</label><input id="branch-user-password-confirmation" class="form-control" type="password" name="password_confirmation" minlength="8" required></div>
              </div>
              <div class="merchant-form-actions"><button class="btn admin-btn-primary" type="submit"><i class="fas fa-user-plus mr-2"></i>Create branch user</button></div>
            </form>
          @endif
        </div>
      </div>

      <div class="card admin-card">
        <div class="admin-card-header"><div><span class="admin-eyebrow">Existing access</span><h2>Assigned users</h2></div><span class="dashboard-soft-badge">{{ $merchantUsers->count() }} users</span></div>
        <div class="card-body p-0"><div class="table-responsive">
          <table class="table dashboard-data-table mb-0">
            <thead><tr><th>User</th><th>Branch and access</th><th class="text-right">Action</th></tr></thead>
            <tbody>
              @forelse ($merchantUsers as $assignment)
                <tr>
                  <td><strong>{{ trim($assignment->user->firstname.' '.$assignment->user->lastname) }}</strong><small class="d-block text-muted">{{ $assignment->user->email }}</small></td>
                  <td colspan="2">
                    <form class="d-flex align-items-center justify-content-between flex-wrap" method="POST" action="{{ route('merchant.dashboard.users.update', $assignment) }}">
                      @csrf @method('PATCH')
                      <div class="d-flex align-items-center flex-wrap">
                        <select class="form-control mr-2 mb-2" name="partner_location_id" required>@foreach ($branches as $branch)<option value="{{ $branch->id }}" @selected((int) $assignment->partner_location_id === (int) $branch->id)>{{ $branch->address_1 }}{{ $branch->city ? ', '.$branch->city : '' }}</option>@endforeach</select>
                        <select class="form-control mr-2 mb-2" name="active" required><option value="1" @selected($assignment->active)>Active</option><option value="0" @selected(! $assignment->active)>Disabled</option></select>
                      </div>
                      <button class="btn admin-btn-secondary mb-2" type="submit">Save</button>
                    </form>
                  </td>
                </tr>
              @empty
                <tr><td colspan="3" class="dashboard-table-empty">No branch users have been created.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div></div>
      </div>
    </div>
  </div></section>
</div>
@endsection
