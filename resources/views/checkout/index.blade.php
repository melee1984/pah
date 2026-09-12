@extends('templates.empty')
@section('title', 'Checkout')

@section('content')

	{{-- Check if cart is empty --}}
	@if ($cart->isEmpty())
		<section class="d-flex flex-column align-items-center justify-content-center py-5 bg-light">
			<i class="icofont-basket icon-theme-red"></i>
			<h2 class="mb-2">Your Basket is Empty</h2>
			<p class="text-muted mb-4 text-center">
				Looks like you haven’t added anything to your basket yet.
			</p>
			<a href="{{ route('restaurant.show') }}" class="btn btn-pahatud mt-4">
				Start Shopping
			</a>
		</section>
	@else 
		<checkout-form></checkout-form>
	@endif 
	
@endsection