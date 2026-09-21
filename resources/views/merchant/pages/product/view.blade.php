@extends('merchant.template.main')

@section('content')
  	
	<div class="content-wrapper admin-content-wrapper">
	<section class="content">
	    <div class="container-fluid merchant-products-container">
	    	<merchant-products-view :categories="{{ $categories }}" :merchant="{{ Auth()->user()->merchant }}" ></merchant-products-view>
		</div>
	</section>
</div>

@endsection
