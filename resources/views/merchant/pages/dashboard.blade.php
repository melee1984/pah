@extends('merchant.template.main')

@section('content')

<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid">
      <div class="admin-dashboard-hero merchant-dashboard-hero">
        <div><span class="admin-eyebrow">Merchant overview</span><h1>Welcome back, {{ Auth::User()->firstname ?: Auth::User()->fullname }}</h1><p>Track sales, respond to new orders, and keep {{ Auth::User()->merchant->restaurant_name }} running smoothly.</p></div>
        <div class="admin-dashboard-actions"><a href="{{ route('merchant.dashboard.report.salestoday') }}" class="btn admin-btn-secondary"><i class="fas fa-chart-line mr-2"></i>Sales report</a><a href="{{ route('merchant.dashboard.orders') }}" class="btn admin-btn-primary"><i class="fas fa-shopping-bag mr-2"></i>Manage orders</a></div>
      </div>
    </div></section>
    <section class="content">
      <div class="container-fluid">
        @include('merchant.partials.setup-banner')
        <order-summary-view></order-summary-view>
        <div class="card admin-card">
          <div class="admin-card-header">
            <div><span class="admin-eyebrow">Store network</span><h2>Branch locations</h2></div>
            <a href="{{ route('merchant.dashboard.location') }}" class="btn admin-btn-secondary"><i class="fas fa-map-marker-alt mr-2"></i>Manage branches</a>
          </div>
          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table dashboard-data-table mb-0">
                <thead><tr><th>Branch</th><th>City</th><th>Contact</th><th>Status</th></tr></thead>
                <tbody>
                  @forelse ($branches as $branch)
                    <tr>
                      <td><strong>{{ $branch->address_1 }}</strong>@if ($branch->address_2)<small class="d-block text-muted">{{ $branch->address_2 }}</small>@endif</td>
                      <td>{{ $branch->city ?: 'Not provided' }}</td>
                      <td>{{ $branch->mobile ?: ($branch->telephone ?: 'Not provided') }}</td>
                      <td><span class="dashboard-soft-badge">{{ $branch->active ? 'Active' : 'Inactive' }}</span></td>
                    </tr>
                  @empty
                    <tr><td colspan="4" class="dashboard-table-empty">No branch locations have been added yet.</td></tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
        <div class="admin-section-heading"><div><span class="admin-eyebrow">Live operations</span><h2>Recent and incoming orders</h2></div><p>Order activity refreshes automatically.</p></div>
        <order-listing-view></order-listing-view>
        @include('merchant.partials.documentation')
      </div>
    </section>
  </div>

@endsection
