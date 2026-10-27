@extends('merchant.template.main')

@section('content')
  	
	<div class="content-wrapper admin-content-wrapper">

  	<section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading">
          <div><span class="admin-eyebrow">Catalog settings</span><h1>Categories</h1><p>Organize your products into clear groups for easier browsing.</p></div>
          <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Categories</li></ol>
        </div>
      </div>
    </section>

  	<section class="content">
      <div class="container-fluid">
			<merchant-category-view></merchant-category-view>
		</div>
	</section>
</div>

@endsection
