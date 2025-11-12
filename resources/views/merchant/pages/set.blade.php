@extends('merchant.template.empty')

@section('content')
<div class="card">
  <div class="card-body login-card-body">
    <p class="login-box-msg">Set your Password</p>
    @include('includes.error')
    <form action="{{ route('merchant.login.submit') }}" method="post">
      @csrf()
        <div class="input-group mb-3">
        <input type="text" class="form-control" placeholder="restaurant" name="name" value="{{ old('name') }}" disabled="">
      </div>

      <div class="input-group mb-3">
        <input type="email" class="form-control" placeholder="Email" name="email" value="{{ old('email') }}" disabled="">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-envelope"></span>
          </div>
        </div>
      </div>
      <div class="input-group mb-3">
        <input type="password" class="form-control" placeholder="Password" name="password">
        <div class="input-group-append">
          <div class="input-group-text">
            <span class="fas fa-lock"></span>
          </div>
        </div>
      </div>
      <div class="row">
       
        <!-- /.col -->
        <div class="col-4">
          <button type="submit" class="btn btn-pahatud btn-block">Submit</button>
        </div>
        <!-- /.col -->
      </div>
    </form>

    <br/><br/>
  
  </div>
  <!-- /.login-card-body -->
</div>

@endsection