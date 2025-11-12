@extends('merchant.template.empty')

@section('content')


<div class="card">
  <div class="card-body login-card-body">
    <p class="login-box-msg">Reset Password</p>
    @include('includes.error')
    
   <form action="{{ route('merchant.reset.submit') }}" method="post">
      @csrf()
      <input type="hidden" name="token" value="{{ $token }}">
      <div class="input-group mb-3">
        <input type="password" class="form-control" placeholder="Enter Password" name="password" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>
      <div class="input-group mb-3">
        <input type="password" class="form-control" placeholder="Confirm Password" name="password_confirmation" required>
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>
      <div class="row">
       
        <!-- /.col -->
        <div class="col-12">
          <button type="submit" class="btn btn-pahatud btn-block">Update Password</button>
        </div>
        <!-- /.col -->
      </div>
    </form>

    <br/><br/>
  
  </div>
  <!-- /.login-card-body -->
</div>

@endsection