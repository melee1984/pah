@extends('templates.customer-account')
@section('title', 'My Orders')
@section('content')
<p class="customer-welcome">Follow your orders and review the details of each purchase.</p>
<section class="customer-card">@include('customer.order-list')
@if($orders->hasPages())<nav class="customer-pagination" aria-label="Order pages">@if($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>@if($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Next →</a>@endif</nav>@endif
</section>
@endsection
