@extends('templates.full')

@section('content')
    <br/><br/>

            <br>
            <br>
                <div class="form-group row">
                    <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                       <h5>{{ __('Reset Password') }}</h5>
                    </div>
                </div>
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div class="form-group row">

                        <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ $email ?? old('email') }}" required autocomplete="email" autofocus placeholder="Enter Email addresss">

                            @error('email')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Enter Password">

                            @error('password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" placeholder="Confirm Password">
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                            <button type="submit" class="btn-block btn-xs food-btn style-2"><span>  {{ __('Reset Password') }}</span></button> 
                        </div>
                    </div>
                </form>
 
@endsection
