@extends('templates.inside')
@section('title', $partner->restaurant_name)
@section('og')
	 @if ($partner->account_type_id != 4)
		<meta property="og:image:type" content="image/jpeg" />
		<meta property="og:image:width" content="500" />
		<meta property="og:image:height" content="500" />
		<meta property="og:image:alt" content="{{ $partner->restaurant_name }} Order Online from Pahatud Delivery" />
		<meta property="og:title" content="{{ $partner->restaurant_name }} Order Online from Pahatud Delivery" />
		<meta property="og:url" content="{{ route('restaurant.view', $partner ) }}" />
		<meta property="og:type" content="website" />
		<meta property="og:image" content="{{ asset('uploads/user/'.$partner->id.'/'.$partner->img) }}" />
		<meta property="og:image:secure_url" content="{{ asset('uploads/user/'.$partner->id.'/'.$partner->img) }}" />
	@endif
	
	@if ($partner->description!="")
		<meta property="og:description" content="{{ $partner->description }}" />
	@else 
		<meta property="og:description" content="Send Flower to your love one Online via Pahatud Deliver www.pahatud.com. Thanks!!! " />

		<meta property="og:image:type" content="image/jpeg" />
		<meta property="og:image:width" content="500" />
		<meta property="og:image:height" content="500" />
		<meta property="og:image:alt" content="{{ $partner->restaurant_name }} Order Online from Pahatud Delivery" />
		<meta property="og:title" content="{{ $partner->restaurant_name }} Order Online from Pahatud Delivery" />
		<meta property="og:url" content="{{ route('flowerstore.view', $partner ) }}" />


		<meta property="og:type" content="website" />
		<meta property="og:image" content="{{ asset('uploads/user/'.$partner->id.'/'.$partner->img) }}" />
		<meta property="og:image:secure_url" content="{{ asset('uploads/user/'.$partner->id.'/'.$partner->img) }}" />
	@endif

@endsection

@section('content')
<div class="row">
		
	<div class="col-md-8 col-lg-8 col-xl-9 pr-0 p-0">

		@if ($partner->banner!="")
			<section class="page-header style-2" style="background: url(../../uploads/user/{{ $partner->id }}/banner/{{$partner->banner}}); background-position: center; background-size: cover; background-repeat: no-repeat;border: 1px solid whitesmoke;padding: 202px;">
	            <div class="container">
	                <div class="page-title text-center">
	                </div>
	            </div>
	        </section>
	    @else 
		    @if ($partner->account_type_id != 4)
		   		<section class="page-header style-2">
		            <div class="container">
		                <div class="page-title text-center">
		                </div>
		            </div>
		        </section>
		     @endif
        @endif

		<front-detailed-list :restaurant="{{ $partner }}" :addingthisformerchant="{{ $addingForThisMerchant }}" ></front-detailed-list>
	</div>
	<div class="col-md-4 col-lg-4 col-xl-3 pl-0">
		<front-cart-checkout :store_open="{{ $partner->store_open }}"></front-cart-checkout> 
	</div>
</div>
@endsection