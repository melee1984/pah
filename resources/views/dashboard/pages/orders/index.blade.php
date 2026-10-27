@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid">
        <div class="admin-page-heading dashboard-operations-heading">
            <div><span class="admin-eyebrow">Marketplace operations</span><h1>Orders</h1><p>Review new orders, coordinate fulfilment, assign riders, and track completed sales.</p></div>
            <div class="admin-dashboard-actions"><a href="{{ route('dashboard.data') }}" class="btn admin-btn-secondary"><i class="fas fa-chart-pie mr-2"></i>Overview</a><a href="{{ route('dashboard.report.orders') }}" class="btn admin-btn-primary"><i class="fas fa-file-invoice mr-2"></i>Sales report</a></div>
        </div>
    </div></section>
    <section class="content"><div class="container-fluid">
        <div class="dashboard-page-note"><span><i class="fas fa-bolt"></i></span><div><strong>Live order queue</strong><p>The list refreshes automatically. Unassigned incoming orders are highlighted so they can be handled first.</p></div></div>
        <order-listing-view></order-listing-view>
    </div></section>
</div>
@endsection
