@extends('merchant.template.main')

@section('content')
  	
	<div class="content-wrapper admin-content-wrapper">

	<section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading">
          <div><span class="admin-eyebrow">Store settings</span><h1>Locations</h1><p>Manage the addresses and contact details customers see for your store.</p></div>
          <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Locations</li></ol>
        </div>
      </div>
    </section>

  	<section class="content">
      	<div class="container-fluid">
			<merchant-location-view></merchant-location-view>
		</div>
	</section>
</div>

@endsection
