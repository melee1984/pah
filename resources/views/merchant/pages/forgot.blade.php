@extends('merchant.template.empty')

@section('content')


<div class="card">
  <div class="card-body login-card-body">
    <p class="login-box-msg">Forgot Password? </p>
    @include('includes.error')
    
    <merchant-forgot-form></merchant-forgot-form>

    <br/><br/>

    <p class="mb-1">
      <a href="{{ route('merchant.login') }}">I already have a Merchant Account</a>
    </p>
    <p class="mb-0">
      <a href="{{ route('merchant.register') }}" class="text-center">Register a new Merchant Account</a>
    </p>
  </div>
  <!-- /.login-card-body -->
</div>

@endsection