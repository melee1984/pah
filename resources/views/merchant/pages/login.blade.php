@extends('merchant.template.empty')

@section('content')
<div class="card pahatud-login-card">
  <div class="card-body login-card-body">
    <span class="pahatud-login-role"><i class="fas fa-store"></i> Merchant partner</span>
    <h1>Welcome back</h1>
    <p class="login-box-msg">Sign in to manage your store and incoming orders.</p>
    @include('includes.error')
    <form action="{{ route('merchant.login.submit') }}" method="post">
      @csrf()
      <label for="merchant-email">Email address</label>
      <div class="input-group mb-3">
        <input id="merchant-email" type="email" class="form-control" placeholder="you@example.com" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-envelope"></span>
          </div>
        </div>
      </div>
      <label for="merchant-password">Password</label>
      <div class="input-group mb-3">
        <input id="merchant-password" type="password" class="form-control" placeholder="Enter your password" name="password" autocomplete="current-password" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>
      <div class="pahatud-login-options">
        <div>
          <div class="icheck-primary">
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">
              Remember me
            </label>
          </div>
        </div>
        <!-- /.col -->
        <a href="{{ route('merchant.forgot') }}">Forgot password?</a>
      </div>
      <button type="submit" class="btn pahatud-login-submit btn-block">Sign in to dashboard <i class="fas fa-arrow-right"></i></button>
    </form>
    <p class="pahatud-login-register">Interested in joining Pahatud? <a href="{{ route('merchant.register') }}">Become a merchant</a></p>
  </div>
  <!-- /.login-card-body -->
</div>

@endsection
