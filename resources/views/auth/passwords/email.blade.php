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
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('password.email') }}">
                        @csrf

                        <div class="form-group row">

                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus placeholder="Enter your register email address">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-6 col-xs-6 col-lg-6 offset-md-3 offset-lg-3">
                                <button type="submit" class="btn-block btn-xs food-btn style-2"><span>{{ __('Send Reset Password Link') }}</span></button> 
                            </div>
                        </div>
                    </form>
   
@endsection
