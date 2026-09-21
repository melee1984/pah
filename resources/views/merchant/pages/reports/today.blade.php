@extends('merchant.template.main')

@section('content')
    
    <div class="content-wrapper admin-content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading">
          <div><span class="admin-eyebrow">Sales reporting</span><h1>Completed sales</h1><p>Review completed orders, fees, commission, and net earnings by date.</p></div>
          <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Sales</li></ol>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <today-report></today-report>
      </div>
  </section>
</div>

@endsection
