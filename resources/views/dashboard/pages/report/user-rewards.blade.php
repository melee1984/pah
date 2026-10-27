@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Customer loyalty</span>
                    <h1>User Reward Points Report</h1>
                    <p>Review every customer's available points, rewarded orders, qualifying spend, and reversals.</p>
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
                <form class="dashboard-filter-bar" method="GET" action="{{ route('dashboard.report.user-rewards') }}">
                    <div>
                        <label for="search">Customer</label>
                        <input class="form-control" id="search" name="search" type="search" value="{{ $search }}" placeholder="Name, email, or mobile">
                    </div>
                    <div>
                        <label for="sort">Sort by</label>
                        <select class="form-control" id="sort" name="sort">
                            <option value="points_desc" @selected($sort === 'points_desc')>Highest points</option>
                            <option value="points_asc" @selected($sort === 'points_asc')>Lowest points</option>
                            <option value="latest" @selected($sort === 'latest')>Most recent reward</option>
                            <option value="customer" @selected($sort === 'customer')>Customer name</option>
                        </select>
                    </div>
                    <div class="dashboard-filter-actions">
                        <button class="btn admin-btn-primary" type="submit"><i class="fas fa-filter mr-2"></i>Apply filters</button>
                        <a class="btn admin-btn-secondary" href="{{ route('dashboard.report.user-rewards') }}">Reset</a>
                    </div>
                </form>
            </div>

            <div class="admin-stat-grid">
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-star"></i></span><div><small>Available points</small><strong>{{ number_format($metrics['available_points']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-users"></i></span><div><small>Reward customers</small><strong>{{ number_format($metrics['customers']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-shopping-bag"></i></span><div><small>Rewarded orders</small><strong>{{ number_format($metrics['rewarded_orders']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-undo-alt"></i></span><div><small>Reversed points</small><strong>{{ number_format($metrics['reversed_points']) }}</strong></div></article>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div>
                        <span class="admin-eyebrow">Point balances</span>
                        <h2>Customer rewards</h2>
                        <p>{{ number_format($customers->total()) }} {{ Str::plural('customer', $customers->total()) }} found · ₱{{ number_format($metrics['qualifying_spend'], 2) }} qualifying spend.</p>
                    </div>
                    <span class="dashboard-soft-badge">₱{{ number_format(config('rewards.php_per_point'), 2) }} = 1 point</span>
                </div>

                @if ($customers->isEmpty())
                    <div class="admin-empty-state"><span><i class="far fa-star"></i></span><h3>No reward customers found</h3><p>Points will appear after qualifying orders are delivered.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Customer</th><th>Contact</th><th>Available points</th><th>Lifetime awarded</th><th>Reversed</th><th>Rewarded orders</th><th>Qualifying spend</th><th>Last reward</th></tr></thead>
                            <tbody>
                            @foreach ($customers as $customer)
                                @php($customerName = trim(($customer->firstname ?? '').' '.($customer->lastname ?? '')))
                                <tr>
                                    <td><strong>{{ $customerName ?: 'Customer '.$customer->user_id }}</strong><small>Customer ID {{ $customer->user_id }}</small></td>
                                    <td><strong>{{ $customer->email ?: 'No email' }}</strong><small>{{ $customer->mobile ?: 'No mobile number' }}</small></td>
                                    <td><strong class="admin-money">{{ number_format($customer->available_points) }} pts</strong></td>
                                    <td><strong>{{ number_format($customer->lifetime_points) }} pts</strong></td>
                                    <td><strong>{{ number_format($customer->reversed_points) }} pts</strong></td>
                                    <td><strong>{{ number_format($customer->rewarded_orders) }}</strong></td>
                                    <td><strong>₱{{ number_format($customer->qualifying_spend, 2) }}</strong></td>
                                    <td><strong>{{ $customer->latest_reward_at ? \Carbon\Carbon::parse($customer->latest_reward_at)->format('M d, Y') : '—' }}</strong><small>{{ $customer->latest_reward_at ? \Carbon\Carbon::parse($customer->latest_reward_at)->format('g:i A') : '' }}</small></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $customers->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
