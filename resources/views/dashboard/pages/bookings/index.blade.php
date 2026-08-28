@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid">
        <div class="admin-page-heading dashboard-operations-heading">
            <div><span class="admin-eyebrow">Delivery operations</span><h1>Bookings</h1><p>Monitor delivery job orders, rider assignments, status changes, and completed requests.</p></div>
            <div class="admin-dashboard-actions"><a href="{{ route('dashboard.data') }}" class="btn admin-btn-secondary"><i class="fas fa-chart-pie mr-2"></i>Overview</a><a href="{{ route('dashboard.booking.add') }}" class="btn admin-btn-primary"><i class="fas fa-plus mr-2"></i>New booking</a></div>
        </div>
    </div></section>
    <section class="content"><div class="container-fluid">
        <div class="dashboard-page-note"><span><i class="fas fa-motorcycle"></i></span><div><strong>Live booking queue</strong><p>Use this page to assign riders and update delivery progress. The queue refreshes automatically.</p></div></div>
        <booking-listing-view></booking-listing-view>
    </div></section>
</div>
@endsection
