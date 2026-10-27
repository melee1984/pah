@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Dine-in settings</span><h1>Manage Tables</h1><p>Configure dining tables and their availability for each dine-in branch.</p></div>
                <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Manage Tables</li></ol>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid"><merchant-tables-view></merchant-tables-view></div>
    </section>
</div>
@endsection
