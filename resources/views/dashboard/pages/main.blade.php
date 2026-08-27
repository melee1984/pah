@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="admin-dashboard-hero">
                <div>
                    <span class="admin-eyebrow">Operations overview</span>
                    <h1>Welcome back, {{ Auth::User()->firstname ?: Auth::User()->fullname }}</h1>
                    <p>Monitor marketplace activity and manage the Pahatud agent network.</p>
                </div>
                <div class="admin-dashboard-actions">
                    <a href="{{ route('dashboard.agents.index') }}" class="btn admin-btn-secondary">
                        <i class="fas fa-user-tie mr-2"></i>Manage agents
                    </a>
                    <a href="{{ route('dashboard.booking.add') }}" class="btn admin-btn-primary">
                        <i class="fas fa-plus mr-2"></i>Add booking
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="admin-stat-grid">
                <article class="admin-stat-card">
                    <span class="admin-stat-icon"><i class="fas fa-user-tie"></i></span>
                    <div><small>Total agents</small><strong>{{ number_format($agentMetrics['total']) }}</strong></div>
                </article>
                <article class="admin-stat-card">
                    <span class="admin-stat-icon"><i class="fas fa-user-check"></i></span>
                    <div><small>Active agents</small><strong>{{ number_format($agentMetrics['active']) }}</strong></div>
                </article>
                <article class="admin-stat-card">
                    <span class="admin-stat-icon"><i class="fas fa-key"></i></span>
                    <div><small>Agent setup pending</small><strong>{{ number_format($agentMetrics['setup_pending']) }}</strong></div>
                </article>
                <article class="admin-stat-card admin-stat-card-red">
                    <span class="admin-stat-icon"><i class="fas fa-wallet"></i></span>
                    <div><small>Agent commission earned</small><strong>&#8369;{{ number_format($agentMetrics['commission'], 2) }}</strong></div>
                </article>
            </div>

            <div class="admin-section-heading">
                <div><span class="admin-eyebrow">Live operations</span><h2>Orders and bookings</h2></div>
                <p>Current marketplace activity appears below.</p>
            </div>

            <div class="row">
                <section class="col-lg-12 connectedSortable">
                    <order-listing-view></order-listing-view>
                </section>
                <section class="col-lg-12 connectedSortable">
                    <booking-listing-view></booking-listing-view>
                </section>
            </div>
        </div>
    </section>
</div>
@endsection
