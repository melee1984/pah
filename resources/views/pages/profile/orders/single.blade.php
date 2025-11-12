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
					<div class="col-md-4">
				<div class="card">
					<div class="card-header">
						Delivery
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
	   		<div class="col-md-8">

	   			@if (@$cart)
	   			<!-- Invoice --> 
	   			<div class="col-md-12">
			      <div class="invoice">
			         <!-- begin invoice-company -->
			         <div class="invoice-company text-left f-w-600">
			         	<div class="row">
			         	<div class="col-md-6">
			         		<a  href="{{ route('restaurant.view', $cart->partner) }}">
			         			<img src="{{ $cart->partner->image() }}" alt="" class="img-responsive" width="50"> 
			         		</a> {{ $cart->partner->restaurant_name }}
			         	</div>
			         	<div class="col-md-6 text-right">
				         		 <span class=" hidden-print">
				            <a href="javascript:;" onclick="window.print()" class="btn btn-sm btn-white m-b-10 p-l-5"><i class="fa fa-print t-plus-1 fa-fw fa-lg"></i> Print</a>
				            </span>
			         	</div>
			         	</div>
			         </div>
			         <!-- end invoice-company -->
			         <!-- begin invoice-header -->
			         <div class="invoice-header">
			            <div class="invoice-from">
			               <small>from</small>
			               <address class="m-t-5 m-b-5">
			                  <strong class="text-inverse">{{ $cart->partner->restaurant_name }}</strong><br>

			                  {{ $cart->partnerlocation->address_1 }} <br>
			                  {{ $cart->partnerlocation->address_2 }} <br>
			                  Phone:  {{ $cart->partnerlocation->mobile }}  <br>
			                  Mobile:{{ $cart->partnerlocation->telephone }} <br>
			                  
			               </address>
			            </div>
			            <div class="invoice-to">
			               <small>to</small>
			               <address class="m-t-5 m-b-5">
			                  <strong class="text-inverse">{{ $cart->fullname }}</strong><br>
			                  @if ($cart->address)
			                  	{{ $cart->address->address_1 }}<br>
			                  @endif
			                  Mobile: {{ $cart->mobile }} <br>
			                  Email: {{ $cart->email }}

			               </address>
			            </div>
			            <div class="invoice-date">
			               <small>Receipt</small>
			               <div class="date text-inverse m-t-5">{{ $order->created_at->format('m/d/Y h:m a') }}</div>
			               <div class="invoice-detail">

			                  	Order #{{ $cart->order_no }}<br>
			                  	@if ($cart->order)
			                  		@if ($cart->order->status)
					                @if($cart->order->status->id == 1)
								  		<span class="badge badge-danger">{{ $cart->order->status->title }}</span> 
									@elseif ($cart->order->status->id == 2)
										<span class="badge badge-success">{{ $cart->order->status->title }}</span> 
					                @elseif ($cart->order->status->id == 3)
					                	<span class="badge badge-warning">{{ $cart->order->status->title }}</span> 
					                @elseif ($cart->order->status->id == 4)
					                	<span class="badge badge-secondary">{{ $cart->order->status->title }}</span> 
					                @endif

					            @else 
					               <span class="badge badge-danger">{{ __('Pending') }}</span> 
				                @endif
				                
				                @endif 
			               </div>
			            </div>
			         </div>
			         <!-- end invoice-header -->
			         <!-- begin invoice-content -->
			         <div class="invoice-content">
			            <!-- begin table-responsive -->
			            <div class="table-responsive">
			               <table class="table table-invoice">
			                  <thead>
			                     <tr>
			                        <th>Item</th>
			                        <th class="text-center" width="10%">Amount</th>
			                        <th class="text-center" width="10%">Qty</th>
			                        <th class="text-right" width="20%">Sub Total</th>
			                     </tr>
			                  </thead>
			                  <tbody>
			                  	@forelse($cart->details as $detail) 
			                  		<?php 
			                  			$detail->variance_content = unserialize($detail->variance_content);
			                  		?>
			                     <tr>
			                        <td>
			                           <span class="text-inverse"> {{ $detail->item->title }}</span><br>
			                           @foreach($detail->variance_content as $variant)
			                           		<small>+ {{ $variant['title'] }}</small><br>
			                           @endforeach
			                        </td>
			                        <td class="text-center">{{ $detail->price + $detail->variance_total}}</td>
			                        <td class="text-center">{{ $detail->qty }}</td>
			                        <td class="text-right">{{ ($detail->price + $detail->variance_total) * $detail->qty }} </td>
			                     </tr>
			                     @empty
			                     <tr>
			                     	<td colspan="4">No record found</td>
			                     </tr>
			                     @endforelse 
			                    
			                  </tbody>
			               </table>
			            </div>
			            <!-- end table-responsive -->
			            <!-- begin invoice-price -->

			            <?php 
							$summary = $cart->cartItemSummary();
						?>

			            <div class="invoice-price">
			               <div class="invoice-price-left">
			                  <div class="invoice-price-row">
			                     <div class="sub-price">
			                        <small>SUBTOTAL</small>
			                        <span class="text-inverse">{{$summary['sub_total'] }}</span>
			                     </div>
			                     <div class="sub-price">
			                     	<small>DELIVERY FEE</small>
			                        <i class="fa fa-plus text-muted">{{ $summary['delivery_fee'] }}</i>
			                     </div>
			                     <div class="sub-price">
			                        <small>DISCOUNT (5.4%)</small>
			                        <span class="text-inverse">{{ $summary['discount'] }}</span>
			                     </div>
			                  </div>
			               </div>
			               <div class="invoice-price-right">
			                  <small>TOTAL</small> <span class="f-w-600">{{ $summary['total'] }}</span>
			               </div>
			            </div>
			            <!-- end invoice-price -->
			         </div>
			         <!-- end invoice-content -->
			         <!-- begin invoice-note -->
			         <div class="invoice-note">
			            * If you want to follow up regarding your order. <br>Please connect to our facebook page, send us your Order # and will get back to you ASAP. Thank you
			         </div>
			         <!-- end invoice-note -->
			         <!-- begin invoice-footer -->
			         <div class="invoice-footer">
			            <p class="text-center m-b-5 f-w-600">
			               THANK YOU FOR YOUR BUSINESS
			            </p>
			            <p class="text-center">
			               <span class="m-r-10"><i class="fa fa-fw fa-lg fa-globe"></i> pahatud.com</span>
			               <span class="m-r-10"><i class="fa fa-fw fa-lg fa-phone-volume"></i> M:09162986547</span>
			               <span class="m-r-10"><i class="fa fa-fw fa-lg fa-envelope"></i> order@pahatud.com</span>
			            </p>
			         </div>
			         <!-- end invoice-footer -->
			      </div>
			   </div>
	   			<!-- End Invoice -->
	   		</div>
	   		@endif 
	   		
				<!-- end list -->    		

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