@extends('merchant.template.empty')

@section('content')
<div class="card pahatud-login-card">
  <div class="card-body login-card-body">
    <span class="pahatud-login-role"><i class="fas fa-shield-alt"></i> First sign-in security</span>
    <h1>Create your password</h1>
    <p class="login-box-msg">Replace the temporary password from your account email before accessing the merchant dashboard.</p>
    @include('includes.error')
    <form action="{{ route('merchant.password.update') }}" method="post">
      @csrf
      <label for="current_password">Temporary password</label>
      <div class="input-group mb-3">
        <input id="current_password" type="password" class="form-control" name="current_password" autocomplete="current-password" required autofocus>
        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-key"></span></div></div>
      </div>
      <label for="password">New password</label>
      <div class="input-group mb-3">
        <input id="password" type="password" class="form-control" name="password" minlength="8" autocomplete="new-password" required>
        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
      </div>
      <label for="password_confirmation">Confirm new password</label>
      <div class="input-group mb-3">
        <input id="password_confirmation" type="password" class="form-control" name="password_confirmation" minlength="8" autocomplete="new-password" required>
        <div class="input-group-append"><div class="input-group-text"><span class="fas fa-lock"></span></div></div>
      </div>
      <p class="text-muted">Use at least 8 characters and choose a password different from the temporary one.</p>
      <button type="submit" class="btn pahatud-login-submit btn-block">Save password and continue <i class="fas fa-arrow-right"></i></button>
    </form>
    <p class="pahatud-login-register"><a href="{{ route('merchant.logout') }}">Sign out instead</a></p>
  </div>
</div>
@endsection
