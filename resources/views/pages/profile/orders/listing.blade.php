@extends('templates.empty')
@section('title', 'Order Status - ')
@section('content')


<section class="about about-page padding-tb">
    <div class="container" id="historybookings">
    	<div class="introname">Hello {{ ucfirst(Auth::User()->firstname) }}! How are you today? </div> 
    	<h3>My Orders</h3>
	   	<hr>
    	<div class="row">

    		@if (count($orders)<=0)
	    		<div class="col-md-12">
	    			<div class="alert alert-info" role="alert">
						  Your my order record is empty
					</div>
	    		</div>
	    	@else
				<!-- list -->
					<div class="col-md-6">
				<div class="card">
					<div class="card-header">
						Orders
					</div>
					<ul class="list-group">
						@forelse($orders as $order)
						
							@if($cart->order_no == $order->cart->order_no)
								<li class="list-group-item list-group-item-action active" >
							@else 
								<li class="list-group-item list-group-item-action" >
							@endif
									<a href="{{ route('profile.orders.view', $order->cart) }}">
										<small>{{ $order->created_at->format('m/d/Y H:i a') }} - 
										<span class="">
											Order #: {{ $order->cart->order_no }} - 
											@if ($order->status_id=="")
												<span class="badge badge-danger">{{ __('Pending') }}</span>
												@elseif (($order->status_id==1))
												<span class="badge badge-danger">
													{{  $order->status->title }}
												</span>
											@elseif (($order->status_id==2))
												<span class="badge badge-success">
													{{  $order->status->title }}
												</span>
											@elseif (($order->status_id==3))
												<span class="badge badge-warning">
													{{  $order->status->title }}
												</span>
											@elseif (($order->status_id==4))
												<span class="badge badge-secondary">
													{{  $order->status->title }}
												</span>
											@else  
												{{  $order->status->title }}
											@endif
										</small>
									</a>
							</li>
						@empty
							<li>No Record found</li>
						@endforelse
						<li class="list-group-item list-group-item-action" >
							
						</li>
					</ul>
				</div>
			</div>
			

			@endif
	   			

	   	</div> 
    </div>
</section>

@endsection


<style>
	
	body{
    margin-top:20px;
    background:#eee;
}

.invoice {
    background: #fff;
    padding: 20px
}

.invoice-company {
    font-size: 20px
}

.invoice-header {
    margin: 0 -20px;
    background: #f0f3f4;
    padding: 20px
}

.invoice-date,
.invoice-from,
.invoice-to {
    display: table-cell;
    width: 1%
}

.invoice-from,
.invoice-to {
    padding-right: 20px
}

.invoice-date .date,
.invoice-from strong,
.invoice-to strong {
    font-size: 16px;
    font-weight: 600
}

.invoice-date {
    text-align: right;
    padding-left: 20px
}

.invoice-price {
    background: #f0f3f4;
    display: table;
    width: 100%
}

.invoice-price .invoice-price-left,
.invoice-price .invoice-price-right {
    display: table-cell;
    padding: 20px;
    font-size: 20px;
    font-weight: 600;
    width: 75%;
    position: relative;
    vertical-align: middle
}

.invoice-price .invoice-price-left .sub-price {
    display: table-cell;
    vertical-align: middle;
    padding: 0 20px
}

.invoice-price small {
    font-size: 12px;
    font-weight: 400;
    display: block
}

.invoice-price .invoice-price-row {
    display: table;
    float: left
}

.invoice-price .invoice-price-right {
    width: 25%;
    background: #2d353c;
    color: #fff;
    font-size: 28px;
    text-align: right;
    vertical-align: bottom;
    font-weight: 300
}

.invoice-price .invoice-price-right small {
    display: block;
    opacity: .6;
    position: absolute;
    top: 10px;
    left: 10px;
    font-size: 12px
}

.invoice-footer {
    border-top: 1px solid #ddd;
    padding-top: 10px;
    font-size: 10px
}

.invoice-note {
    color: #999;
    margin-top: 80px;
    font-size: 85%
}

.invoice>div:not(.invoice-footer) {
    margin-bottom: 20px
}

.btn.btn-white, .btn.btn-white.disabled, .btn.btn-white.disabled:focus, .btn.btn-white.disabled:hover, .btn.btn-white[disabled], .btn.btn-white[disabled]:focus, .btn.btn-white[disabled]:hover {
    color: #2d353c;
    background: #fff;
    border-color: #d9dfe3;
}

</style>