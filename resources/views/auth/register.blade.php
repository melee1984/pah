@extends('templates.full')

@section('content')

<!-- Booking Table Section Start Here -->
        <section class="booking-table ">
           
                <div class="section-wrapper m-0 p-0">
                    <div class="row justify-content-center align-items-center">
                        <div class="col-lg-6 col-12 m-0 p-0">
                           <div class="section-wrapper m-0 p-0">
                              
                            </div>
                            <br>
                            <div class="contact-form m-0 p-0">

                                <div class="">
                                   <h5>{{ __('Register') }}</h5>
                                <p>Required fields with *</p>
                                </div>

                               
                                <br>
                                    @include('includes.error')
                                 <form action="{{ route('register') }}" method="post">
                                    @csrf
                                   <input id="firstname" type="text" class="@error('firstname') is-invalid @enderror" name="firstname" value="{{ old('firstname') }}" required autocomplete="firstname" autofocus placeholder="Firstname *">

                                    @error('firstname')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                    <input id="lastname" type="text" class=" @error('lastname') is-invalid @enderror" name="lastname" value="{{ old('lastname') }}" required autocomplete="lastname" autofocus placeholder="Lastname *">
                                     @error('lastname')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                      <input id="email" type="email" class=" @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="Email Address *">
                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                     <input id="mobile" type="text" class=" @error('mobile') is-invalid @enderror" name="mobile" value="{{ old('mobile') }}" required autocomplete="mobile" autofocus placeholder="Mobile Number*">
                                     @error('mobile')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror

                                    
                                     <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="new-password" placeholder="Password *">

                                    @error('password')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                            
                                    <button type="submit" class="food-btn btn-block style-2"><span>Register</span></button>
                                </form>
                            </div>
                            <br>
                            <hr>
                            <div class="col-12">
                                <div class="contact-form text-center">
                                        <span class="mb-4"><small>or register with</small></span>
                                        <div class="form-group row ">
                                            <a href="/login/facebook?url=request-booking" class="btn btn-xs btn-block fa-facebook p-l-0 l-r-0 m-l-0 m-r-0">
                                                Facebook
                                            </a>
                                        </div>
                                </div>
                                </div>

                        </div>
                    </div>
                </div>
          
        </section>
        <!-- Booking Table Section Ending Here -->

@endsection
