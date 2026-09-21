@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid">
        <div class="admin-dashboard-hero admin-dashboard-hero-overview">
            <div><span class="admin-eyebrow">Business overview</span><h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ Auth::User()->firstname ?: Auth::User()->fullname }}</h1><p>Here is what is happening across Pahatud as of {{ now()->format('l, F j') }}.</p></div>
            <div class="admin-dashboard-actions"><a href="{{ route('dashboard.report.orders') }}" class="btn admin-btn-secondary"><i class="fas fa-chart-line mr-2"></i>View reports</a><a href="{{ route('dashboard.orders') }}" class="btn admin-btn-primary"><i class="fas fa-bell mr-2"></i>{{ number_format($dashboardMetrics['incoming_orders']) }} incoming</a></div>
        </div>
    </div></section>

    <section class="content"><div class="container-fluid">
        <div class="admin-stat-grid dashboard-primary-stats">
            <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-coins"></i></span><div><small>Sales today</small><strong>&#8369;{{ number_format($dashboardMetrics['today_sales'], 2) }}</strong><em>Completed orders</em></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-calendar-week"></i></span><div><small>This week</small><strong>&#8369;{{ number_format($dashboardMetrics['weekly_sales'], 2) }}</strong><em>Sales since Monday</em></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-calendar-alt"></i></span><div><small>This month</small><strong>&#8369;{{ number_format($dashboardMetrics['monthly_sales'], 2) }}</strong><em>{{ now()->format('F') }} sales</em></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-shopping-bag"></i></span><div><small>Total orders</small><strong>{{ number_format($dashboardMetrics['total_orders']) }}</strong><em>{{ number_format($dashboardMetrics['incoming_orders']) }} need attention</em></div></article>
        </div>

        <div class="dashboard-overview-grid">
            <article class="card admin-card dashboard-overview-card dashboard-sales-card">
                <div class="admin-card-header"><div><span class="admin-eyebrow">Sales pulse</span><h2>Last 7 days</h2></div><strong class="dashboard-card-total">&#8369;{{ number_format($salesTrend->sum('sales'), 2) }}</strong></div>
                <div class="dashboard-chart" role="img" aria-label="Completed order sales over the last seven days">
                    @foreach ($salesTrend as $day)
                        <div class="dashboard-chart-column"><span class="dashboard-chart-value">{{ $day['sales'] > 0 ? '₱'.number_format($day['sales'], 0) : '0' }}</span><div class="dashboard-chart-track"><i style="height: {{ max(4, ($day['sales'] / $salesTrendMax) * 100) }}%"></i></div><small>{{ $day['label'] }}</small></div>
                    @endforeach
                </div>
            </article>

            <article class="card admin-card dashboard-overview-card">
                <div class="admin-card-header"><div><span class="admin-eyebrow">Order health</span><h2>Status breakdown</h2></div><span class="dashboard-soft-badge">{{ number_format($dashboardMetrics['total_orders']) }} total</span></div>
                <div class="dashboard-status-list">
                    @forelse ($statusBreakdown as $status)
                        @php
                            $statusTitle = strtolower($status['title']);
                            $statusClass = str_contains($statusTitle, 'deliver') ? 'is-success' : (str_contains($statusTitle, 'cancel') ? 'is-danger' : (str_contains($statusTitle, 'placed') || str_contains($statusTitle, 'pending') ? 'is-warning' : 'is-info'));
                        @endphp
                        <div class="dashboard-status-row"><span class="dashboard-status-dot {{ $statusClass }}"></span><strong>{{ $status['title'] }}</strong><div class="dashboard-progress"><i class="{{ $statusClass }}" style="width: {{ max(2, $status['percentage']) }}%"></i></div><span>{{ number_format($status['count']) }}</span></div>
                    @empty
                        <div class="dashboard-widget-empty">No order activity yet.</div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="dashboard-finance-strip">
            <div><small>Lifetime completed revenue</small><strong>&#8369;{{ number_format($dashboardMetrics['total_revenue'], 2) }}</strong></div>
            <div><small>Platform commission</small><strong>&#8369;{{ number_format($dashboardMetrics['total_commission'], 2) }}</strong></div>
            <div><small>Estimated merchant payout</small><strong>&#8369;{{ number_format($dashboardMetrics['merchant_payout'], 2) }}</strong></div>
            <div><small>Average completed order</small><strong>&#8369;{{ number_format($dashboardMetrics['average_order'], 2) }}</strong></div>
        </div>

        <div class="dashboard-widget-grid">
            <article class="card admin-card dashboard-overview-card">
                <div class="admin-card-header"><div><span class="admin-eyebrow">Leaderboard</span><h2>Top merchants</h2><p>Ranked by completed-order revenue.</p></div><a href="{{ route('dashboard.merchant') }}" class="dashboard-card-link">All merchants</a></div>
                <div class="dashboard-merchant-list">
                    @forelse ($topMerchants as $merchant)
                        <div class="dashboard-merchant-row"><span class="dashboard-rank">{{ $loop->iteration }}</span><div><strong>{{ $merchant['name'] }}</strong><small>{{ number_format($merchant['orders']) }} completed {{ Str::plural('order', $merchant['orders']) }}</small></div><strong>&#8369;{{ number_format($merchant['revenue'], 2) }}</strong></div>
                    @empty
                        <div class="dashboard-widget-empty">Merchant performance will appear after the first completed order.</div>
                    @endforelse
                </div>
            </article>

            <article class="card admin-card dashboard-overview-card">
                <div class="admin-card-header"><div><span class="admin-eyebrow">Latest updates</span><h2>Recent activity</h2><p>The newest changes across orders.</p></div></div>
                <div class="dashboard-activity-list">
                    @forelse ($recentActivity as $activity)
                        <div class="dashboard-activity-row"><span><i class="fas fa-receipt"></i></span><div><strong>Order #{{ $activity->order_no ?: $activity->id }}</strong><p>{{ $activity->status->title ?? 'Order updated' }} · {{ $activity->partner->restaurant_name ?? 'Unknown merchant' }}</p><small>{{ optional($activity->updated_at)->diffForHumans() }}</small></div></div>
                    @empty
                        <div class="dashboard-widget-empty">No recent activity.</div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="admin-section-heading"><div><span class="admin-eyebrow">Quick performance</span><h2>Operational health</h2></div><p>Open a dedicated operations page to take action.</p></div>
        <div class="dashboard-quick-stats">
            <div><span><i class="fas fa-check-circle"></i></span><small>Completion rate</small><strong>{{ number_format($dashboardMetrics['completion_rate'], 1) }}%</strong></div>
            <div><span><i class="fas fa-ban"></i></span><small>Cancellation rate</small><strong>{{ number_format($dashboardMetrics['cancellation_rate'], 1) }}%</strong></div>
            <div><span><i class="fas fa-store"></i></span><small>Active merchants</small><strong>{{ number_format($dashboardMetrics['active_merchants']) }}</strong></div>
            <div><span><i class="fas fa-user-tie"></i></span><small>Active agents</small><strong>{{ number_format($agentMetrics['active']) }}</strong></div>
        </div>

        <div class="dashboard-destination-grid">
            <a href="{{ route('dashboard.orders') }}" class="dashboard-destination-card"><span><i class="fas fa-shopping-bag"></i></span><div><small>Marketplace operations</small><h3>Manage orders</h3><p>Review incoming, completed, and cancelled orders; assign riders and update statuses.</p></div><i class="fas fa-arrow-right"></i></a>
            <a href="{{ route('dashboard.bookings') }}" class="dashboard-destination-card"><span><i class="fas fa-route"></i></span><div><small>Delivery operations</small><h3>Manage bookings</h3><p>Monitor job orders, coordinate riders, and keep delivery requests moving.</p></div><i class="fas fa-arrow-right"></i></a>
        </div>
    </div></section>
</div>
@endsection
