@extends('templates.empty')
@section('title', 'Checkout Success')
@section('content')
<section class="page-header style-2"></section>   
<div class="container" id="success">
	<div class="jumbotron mt-4">
	  <h1 class="display-4">Thank You!</h1>
	  <p class="lead">You have successfully placed your order.</p>
	  <hr class="my-4">
	  <h4 class="text-danger">Your Food is on the way now</h4><br>
	  Order No: <a class="" href="{{ route('profile.orders.view', $cart) }}" role="button">{{ $cart->order_no }}</a> <br>

	  Restaurant: <a href="{{ route('restaurant.view', $cart->partner) }}">{{ $cart->partner->restaurant_name }} - {{ $cart->partnerlocation?$cart->partnerlocation->address_1:''}}</a> <br>
	  Delivery Date: {{ $cart->delivery_date }} @ {{ $cart->delivery_time }} <br>
	  Amount: {{ $cart->cartItemSummary()['total'] }} <br>
	  Payment: {{ $cart->payment->title }} <br>
	  Duration: {{ round($cart->duration) }}mins <br>

	  @foreach($cart->order->orderprocess as $order)
	  	Status: <strong>{{ $order->status->title }}</strong> <br>
	  @endforeach
	  <br><br>
	  <p>To follow up with your order you may check the facebook page and send the Order no to our customer service. </p>
	  <a href="https://www.facebook.com/pahatudDelivery/inbox" target="_new" class="text-danger">Click here to chat</a>
	</div>  
</div>

@endsection


