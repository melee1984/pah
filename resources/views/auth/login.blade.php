@extends('templates.full')

@section('content')
            <br/><br/>
                    
              <div class="form-group row">

                <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                   <h5>{{ __('Login') }}</h5>
                </div>
            </div>

            <form method="POST" action="{{ route('login.submit') }}">
                        @csrf

                        <div class="form-group row">

                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Enter your email address">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">

                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" placeholder="Enter your password">

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row ">
                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <button type="submit" class="btn-block btn-xs food-btn style-2"><span>{{ __('Login') }}</span></button> 
                                @if (Route::has('password.request'))
                                   
                                @endif
                            </div>
                        </div>

                         <div class="form-group row ">
                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                 <a class="text-danger" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                            </div>
                        </div>
                        <div class="form-group row ">
                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <a class="text-danger" href="{{ route('register') }}">
                                    {{ __('Create Account?') }}
                                </a>
                            </div>
                        </div>

                          <br>
                            
                                       <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
<hr>
                                <div class="contact-form text-center">
                                        <span class="mb-4"><small>or login with</small></span>
                                        <div class="form-group row ">
                                            <a href="/login/facebook?url=request-booking" class="btn btn-xs btn-block fa-facebook p-l-0 l-r-0 m-l-0 m-r-0">
                                                Facebook
                                            </a>
                                        </div>
                                </div>
                        </div>

                    </form>
        

@endsection
