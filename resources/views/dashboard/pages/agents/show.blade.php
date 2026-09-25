@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header"><div class="container-fluid"><div class="admin-page-heading">
        <div><span class="admin-eyebrow">Agent management / Agent #{{ $agent->id }}</span><h1>{{ $agent->name }}</h1><p>Account details, application review, and restaurant network.</p></div>
        <a class="btn admin-btn-secondary" href="{{ route('dashboard.agents.index') }}"><i class="fas fa-arrow-left mr-2"></i>All agents</a>
    </div></div></section>

    <section class="content"><div class="container-fluid">
        @if (session('success')) <div class="alert admin-alert-success">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert admin-alert-error">{{ $errors->first() }}</div> @endif

        <div class="admin-stat-grid">
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-store"></i></span><div><small>Approved restaurants</small><strong>{{ number_format($agent->approved_restaurants_count) }}</strong><small>{{ number_format($agent->restaurants_count) }} total enrolled</small></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-percent"></i></span><div><small>Current tier share</small><strong>{{ number_format($agent->commissionPercentage($agent->approved_restaurants_count), 2) }}%</strong></div></article>
            <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-wallet"></i></span><div><small>Commission earned</small><strong>₱{{ number_format($agent->commission_total ?? 0, 2) }}</strong></div></article>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card admin-card"><div class="admin-card-header"><div><h2>Agent details</h2><p>Contact and portal access</p></div></div><div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Full name</dt><dd class="col-sm-8">{{ $agent->name }}</dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><a href="mailto:{{ $agent->email }}">{{ $agent->email }}</a></dd>
                        <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8">{{ $agent->mobile ?: 'Not provided' }}</dd>
                        <dt class="col-sm-4">Joined</dt><dd class="col-sm-8">{{ $agent->created_at?->format('M d, Y g:i A') ?: '—' }}</dd>
                        <dt class="col-sm-4">Last login</dt><dd class="col-sm-8">{{ $agent->last_login_at?->format('M d, Y g:i A') ?: 'Never' }}</dd>
                        <dt class="col-sm-4">Account</dt><dd class="col-sm-8">
                            @if ($agent->review_status === 'declined') <span class="admin-status admin-status-inactive">Declined</span>
                            @elseif (! $agent->active) <span class="admin-status admin-status-pending">Pending review</span>
                            @elseif ($agent->must_change_password) <span class="admin-status admin-status-pending">Awaiting setup</span>
                            @else <span class="admin-status admin-status-active">Active</span> @endif
                        </dd>
                        <dt class="col-sm-4">Reviewed</dt><dd class="col-sm-8">{{ $agent->reviewed_at?->format('M d, Y g:i A') ?: 'Not yet reviewed' }}</dd>
                        @if ($agent->review_message)
                            <dt class="col-sm-4">Admin message</dt><dd class="col-sm-8">{{ $agent->review_message }}</dd>
                        @endif
                    </dl>
                </div></div>
            </div>
            <div class="col-lg-5">
                <div class="card admin-card"><div class="admin-card-header"><div><h2>Application review</h2><p>Decision and message are emailed to the agent</p></div></div><div class="card-body">
                    @if (! $agent->active && $agent->review_status === null)
                        <form method="POST" action="{{ route('dashboard.agents.approve', $agent) }}">
                            @csrf
                            <div class="form-group"><label for="reviewMessage">Message to agent <small>(optional)</small></label><textarea class="form-control" id="reviewMessage" name="message" rows="5" maxlength="2000" placeholder="Add a note about your decision">{{ old('message') }}</textarea></div>
                            <div class="d-flex flex-wrap" style="gap: 10px">
                                <button class="btn admin-btn-primary" type="submit"><i class="fas fa-check mr-1"></i>Approve</button>
                                <button class="btn btn-outline-danger" type="submit" formaction="{{ route('dashboard.agents.decline', $agent) }}"><i class="fas fa-times mr-1"></i>Decline</button>
                            </div>
                        </form>
                    @else
                        <p class="mb-0">{{ $agent->review_status ? 'This application was '. $agent->review_status . '.' : 'This account was created by an administrator.' }}</p>
                    @endif
                </div></div>
            </div>
        </div>

        <div class="card admin-card"><div class="admin-card-header"><div><h2>Linked restaurants</h2><p>{{ number_format($agent->restaurants_count) }} {{ Str::plural('restaurant', $agent->restaurants_count) }} enrolled by this agent</p></div></div>
            @if ($restaurants->isEmpty())
                <div class="admin-empty-state"><span><i class="fas fa-store"></i></span><h3>No restaurants yet</h3><p>Restaurants enrolled by this agent will appear here.</p></div>
            @else
                <div class="table-responsive"><table class="table admin-table mb-0"><thead><tr><th>Restaurant</th><th>Contact</th><th>Location</th><th>Application</th><th>Action</th></tr></thead><tbody>
                    @foreach ($restaurants as $restaurant)
                        <tr>
                            <td><strong>{{ $restaurant->restaurant_name }}</strong><small>Restaurant #{{ $restaurant->id }}</small></td>
                            <td>{{ $restaurant->email ?: '—' }}<small>{{ $restaurant->mobile }}</small></td>
                            <td>{{ $restaurant->city ?: '—' }}</td>
                            <td><span class="admin-status {{ $restaurant->application_status === 'approved' ? 'admin-status-active' : ($restaurant->application_status === 'declined' ? 'admin-status-inactive' : 'admin-status-pending') }}">{{ Str::headline($restaurant->application_status ?: 'Pending review') }}</span></td>
                            <td><a class="btn admin-btn-secondary btn-sm" href="{{ route('dashboard.merchant.application.show', $restaurant->id) }}">View application</a></td>
                        </tr>
                    @endforeach
                </tbody></table></div>
                <div class="admin-pagination">{{ $restaurants->links('pagination::bootstrap-4') }}</div>
            @endif
        </div>
    </div></section>
</div>
@endsection
