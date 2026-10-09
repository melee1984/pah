@extends('templates.customer-account')
@section('title', 'My Support Requests')
@section('content')
<p class="customer-welcome">Track your requests, read replies, or start a new conversation with our support team.</p>
<support-center full-page authenticated></support-center>
@endsection
