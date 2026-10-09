@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid">
        <div class="admin-page-heading dashboard-operations-heading">
            <div><span class="admin-eyebrow">Customer care</span><h1>Support Tickets</h1><p>Manage requests, follow up with customers, and collaborate with your team.</p></div>
            <div class="admin-dashboard-actions"><a href="{{ route('dashboard.data') }}" class="btn admin-btn-secondary"><i class="fas fa-chart-pie mr-2"></i>Overview</a></div>
        </div>
    </div></section>
    <section class="content"><div class="container-fluid">
        <div class="dashboard-page-note"><span><i class="fas fa-headset"></i></span><div><strong>Customer support queue</strong><p>Filter requests, open a conversation, and keep customers updated. Internal notes are visible only to staff.</p></div></div>
        <support-center admin authenticated></support-center>
    </div></section>
</div>
@endsection
