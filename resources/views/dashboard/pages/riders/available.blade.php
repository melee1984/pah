@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Delivery operations</span>
                    <h1>Available riders</h1>
                    <p>See who is online and ready to receive a new booking offer.</p>
                </div>
                <a class="btn admin-btn-secondary" href="{{ route('dashboard.bookings') }}"><i class="fas fa-route mr-2"></i>View bookings</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="admin-stat-grid">
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-check-circle"></i></span><div><small>Ready for booking</small><strong>{{ number_format($metrics['ready']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-broadcast-tower"></i></span><div><small>Marked available</small><strong>{{ number_format($metrics['available']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-route"></i></span><div><small>On active delivery</small><strong>{{ number_format($metrics['busy']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-power-off"></i></span><div><small>Not available</small><strong>{{ number_format($metrics['unavailable']) }}</strong></div></article>
            </div>

            <div class="dashboard-page-note">
                <span><i class="fas fa-info-circle"></i></span>
                <div><strong>Dispatch-ready riders</strong><p>A rider appears here when approved and available, with a saved location, no active delivery, and fewer than {{ $maxPendingOffers }} pending {{ Str::plural('offer', $maxPendingOffers) }}. A push device is optional; assignments still work without one.</p></div>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div><h2>Ready now</h2><p>{{ number_format($riders->total()) }} {{ Str::plural('rider', $riders->total()) }} can receive booking offers</p></div>
                    <form class="admin-search" method="GET" action="{{ route('dashboard.riders.available') }}">
                        <i class="fas fa-search"></i>
                        <input name="search" value="{{ $search }}" placeholder="Search name or mobile" aria-label="Search available riders">
                        @if ($search !== '')<a href="{{ route('dashboard.riders.available') }}" aria-label="Clear search">&times;</a>@endif
                    </form>
                </div>

                @if ($riders->isEmpty())
                    <div class="admin-empty-state">
                        <span><i class="fas fa-motorcycle"></i></span>
                        <h3>{{ $search !== '' ? 'No matching available riders' : 'No riders are ready right now' }}</h3>
                        <p>{{ $search !== '' ? 'Try another name or mobile number.' : 'Riders will appear here after they go available and meet the booking requirements.' }}</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Rider</th><th>Contact</th><th>Status</th><th>Last heartbeat</th><th>Last location</th><th>Notification</th><th>Pending offers</th><th>Action</th></tr></thead>
                            <tbody>
                            @foreach ($riders as $rider)
                                <tr>
                                    <td><div class="admin-agent-cell"><span>{{ mb_strtoupper(mb_substr($rider->name ?: 'R', 0, 1)) }}</span><div><strong>{{ $rider->name ?: 'Unnamed rider' }}</strong><small>Rider #{{ $rider->id }}</small></div></div></td>
                                    <td><strong>{{ $rider->mobile ?: 'No mobile number' }}</strong></td>
                                    <td><span class="admin-status admin-status-active">Ready</span><small>Can receive a booking offer</small></td>
                                    <td><strong>{{ $rider->heartbeat_at ? \Illuminate\Support\Carbon::parse($rider->heartbeat_at)->diffForHumans() : 'Not recorded' }}</strong><small>{{ $rider->heartbeat_at ? \Illuminate\Support\Carbon::parse($rider->heartbeat_at)->format('M d, Y · g:i A') : 'Availability is currently on' }}</small></td>
                                    <td><strong>{{ $rider->location_recorded_at ? \Illuminate\Support\Carbon::parse($rider->location_recorded_at)->diffForHumans() : 'Not recorded' }}</strong><small>{{ $rider->location_recorded_at ? \Illuminate\Support\Carbon::parse($rider->location_recorded_at)->format('M d, Y · g:i A') : 'Location required for dispatch' }}</small></td>
                                    <td>
                                        @if ($rider->push_device_id)
                                            <span class="admin-status admin-status-active">Push enabled</span>
                                            <small>{{ $rider->device_last_seen_at ? 'App seen '.\Illuminate\Support\Carbon::parse($rider->device_last_seen_at)->diffForHumans() : 'Registered device' }}</small>
                                        @else
                                            <span class="admin-status admin-status-pending">No push device</span>
                                            <small>Assignment is still allowed</small>
                                        @endif
                                    </td>
                                    <td><strong>{{ number_format($rider->pending_offer_count) }} / {{ $maxPendingOffers }}</strong><small>Active offer limit</small></td>
                                    <td><a class="btn admin-btn-secondary btn-sm" href="{{ route('dashboard.riders.show', $rider->id) }}"><i class="fas fa-eye mr-1"></i>View rider</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $riders->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
