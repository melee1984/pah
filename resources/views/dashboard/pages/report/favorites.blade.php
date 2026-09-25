@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Customer engagement</span>
                    <h1>Merchant Favorites Report</h1>
                    <p>Verify which merchants customers have hearted and review each current favorite.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>
            @endif

            <div class="card admin-card">
                <form class="dashboard-filter-bar" method="GET" action="{{ route('dashboard.report.favorites') }}">
                    <div>
                        <label for="merchant_id">Merchant</label>
                        <select class="form-control" id="merchant_id" name="merchant_id">
                            <option value="">All merchants</option>
                            @foreach ($merchants as $merchant)
                                <option value="{{ $merchant->id }}" @selected($selectedMerchantId === $merchant->id)>{{ $merchant->restaurant_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="search">Customer or merchant</label>
                        <input class="form-control" id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, email, mobile, or merchant">
                    </div>
                    <div><label for="from">From</label><input class="form-control" id="from" name="from" type="date" value="{{ $from?->toDateString() }}"></div>
                    <div><label for="to">To</label><input class="form-control" id="to" name="to" type="date" value="{{ $to?->toDateString() }}"></div>
                    <div class="dashboard-filter-actions">
                        <button class="btn admin-btn-primary" type="submit"><i class="fas fa-filter mr-2"></i>Apply filters</button>
                        <a class="btn admin-btn-secondary" href="{{ route('dashboard.report.favorites') }}">Reset</a>
                    </div>
                </form>
            </div>

            <div class="admin-stat-grid">
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-heart"></i></span><div><small>Current favorites</small><strong>{{ number_format($metrics['favorites']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-users"></i></span><div><small>Customers</small><strong>{{ number_format($metrics['customers']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-store"></i></span><div><small>Hearted merchants</small><strong>{{ number_format($metrics['merchants']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-calendar-plus"></i></span><div><small>Added this month</small><strong>{{ number_format($metrics['this_month']) }}</strong></div></article>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div><span class="admin-eyebrow">Merchant ranking</span><h2>Most hearted merchants</h2><p>Current favorites within the selected filters.</p></div>
                    <span class="dashboard-soft-badge">Top {{ min(10, $topMerchants->count()) }}</span>
                </div>
                @if ($topMerchants->isEmpty())
                    <div class="admin-empty-state"><span><i class="far fa-heart"></i></span><h3>No merchant favorites found</h3><p>Favorites will appear after customers tap a merchant heart.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Rank</th><th>Merchant</th><th>Current favorites</th><th>Latest favorite</th><th>Status</th></tr></thead>
                            <tbody>
                            @foreach ($topMerchants as $favoriteMerchant)
                                <tr>
                                    <td><strong>#{{ $loop->iteration }}</strong></td>
                                    <td><strong>{{ $favoriteMerchant->partner?->restaurant_name ?? 'Merchant unavailable' }}</strong><small>Merchant ID {{ $favoriteMerchant->partner_id }}</small></td>
                                    <td><strong>{{ number_format($favoriteMerchant->favorites_count) }}</strong><small>{{ Str::plural('customer', $favoriteMerchant->favorites_count) }}</small></td>
                                    <td><strong>{{ optional($favoriteMerchant->latest_favorite_at ? \Carbon\Carbon::parse($favoriteMerchant->latest_favorite_at) : null)->format('M d, Y') ?? '—' }}</strong><small>{{ optional($favoriteMerchant->latest_favorite_at ? \Carbon\Carbon::parse($favoriteMerchant->latest_favorite_at) : null)->format('g:i A') }}</small></td>
                                    <td><span class="dashboard-status-pill {{ $favoriteMerchant->partner?->active ? 'is-success' : 'is-warning' }}">{{ $favoriteMerchant->partner?->active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div><span class="admin-eyebrow">Favorite audit</span><h2>Customer favorites</h2><p>{{ number_format($favorites->total()) }} current {{ Str::plural('favorite', $favorites->total()) }} found.</p></div>
                    <span class="dashboard-soft-badge">Newest first</span>
                </div>
                @if ($favorites->isEmpty())
                    <div class="admin-empty-state"><span><i class="far fa-heart"></i></span><h3>No favorites match these filters</h3><p>Try another merchant, customer, or date range.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Date hearted</th><th>Merchant</th><th>Customer</th><th>Contact</th><th>Merchant status</th></tr></thead>
                            <tbody>
                            @foreach ($favorites as $favorite)
                                @php($customerName = trim(($favorite->user?->firstname ?? '').' '.($favorite->user?->lastname ?? '')))
                                <tr>
                                    <td><strong>{{ $favorite->created_at?->format('M d, Y') ?? '—' }}</strong><small>{{ $favorite->created_at?->format('g:i A') }}</small></td>
                                    <td><strong>{{ $favorite->partner?->restaurant_name ?? 'Merchant unavailable' }}</strong><small>Merchant ID {{ $favorite->partner_id }}</small></td>
                                    <td><strong>{{ $customerName ?: 'Customer unavailable' }}</strong><small>Customer ID {{ $favorite->user_id }}</small></td>
                                    <td><strong>{{ $favorite->user?->email ?? 'No email' }}</strong><small>{{ $favorite->user?->mobile ?: 'No mobile number' }}</small></td>
                                    <td><span class="dashboard-status-pill {{ $favorite->partner?->active ? 'is-success' : 'is-warning' }}">{{ $favorite->partner?->active ? 'Active' : 'Inactive' }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $favorites->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
