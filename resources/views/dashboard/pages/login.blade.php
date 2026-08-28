@extends('dashboard.template.empty')

@section('content')

<div class="card pahatud-login-card">
  <div class="card-body login-card-body">
    <span class="pahatud-login-role"><i class="fas fa-shield-alt"></i> Administration</span>
    <h1>Welcome back</h1>
    <p class="login-box-msg">Sign in to monitor marketplace operations.</p>
    @include('includes.error')
    <form action="{{ route('dashboard.login.submit') }}" method="post">
      @csrf()
      <label for="admin-email">Email address</label>
      <div class="input-group mb-3">
        <input id="admin-email" type="email" class="form-control" placeholder="you@example.com" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-envelope"></span>
          </div>
        </div>
      </div>
      <label for="admin-password">Password</label>
      <div class="input-group mb-3">
        <input id="admin-password" type="password" class="form-control" placeholder="Enter your password" name="password" autocomplete="current-password" required>
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
      </div>
      <button type="submit" class="btn pahatud-login-submit btn-block">Sign in to dashboard <i class="fas fa-arrow-right"></i></button>
    </form>
  </div>
  <!-- /.login-card-body -->
</div>

@endsection
