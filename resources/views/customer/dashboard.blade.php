@extends('templates.customer-account')
@section('title', 'My Dashboard')
@section('content')
<p class="customer-welcome">Welcome back, <strong>{{ auth()->user()->firstname }}</strong>. Your orders, account, and support conversations are all here.</p>
<div class="customer-dashboard-cards">
    <a class="customer-card" href="{{ route('profile.orders') }}"><i class="icofont-fast-delivery" aria-hidden="true"></i><h2>My Orders</h2><strong>{{ $orderCount }} {{ Str::plural('order', $orderCount) }}</strong><p>View order details and the latest status.</p><span>View orders →</span></a>
    <a class="customer-card" href="{{ route('profile.edit') }}"><i class="icofont-ui-user" aria-hidden="true"></i><h2>My Profile</h2><strong>{{ auth()->user()->full_name }}</strong><p>Keep your contact information up to date.</p><span>Edit profile →</span></a>
    <a class="customer-card" href="{{ route('profile.support') }}"><i class="icofont-ui-message" aria-hidden="true"></i><h2>My Support Requests</h2><strong>{{ $ticketCount }} {{ Str::plural('request', $ticketCount) }} · {{ $activeTickets }} active</strong><p>Track progress and read replies from our team.</p><span>Open support requests →</span></a>
</div>
<section class="customer-card customer-recent"><h2>Recent orders</h2>@include('customer.order-list', ['orders' => $recentOrders])</section>
@endsection
