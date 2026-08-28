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
        <order-summary-view></order-summary-view>
        <div class="admin-section-heading"><div><span class="admin-eyebrow">Live operations</span><h2>Recent and incoming orders</h2></div><p>Order activity refreshes automatically.</p></div>
        <order-listing-view></order-listing-view>
      </div>
    </section>
  </div>

@endsection
