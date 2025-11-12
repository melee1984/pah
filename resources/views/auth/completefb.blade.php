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
                                   <h5>{{ __('Complete your register') }}</h5>
                                    <p>Required fields with *</p>
                                    <p>The Facebook account does not provide the email adderess due to your privacy settings. Please manually input your email address to complete your registration. Thank you.</p>
                                </div>

                                <br>
                                    @include('includes.error')
                                 <form action="{{ route('register') }}" method="post">
                                    @csrf

                                   <input type="hidden" name="avatar" value="{{session()->get('avatar')}}">
                                   <input type="hidden" name="provider" value="{{session()->get('provider')}}">
                                   <input type="hidden" type="hidden" name="provider_id" value="{{session()->get('provider_id')}}">

                                    @error('fullname')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror

                                     <input id="lastname" type="text" class="@error('lastname') is-invalid @enderror" name="lastname" value="{{session()->get('firstname')}}" required autocomplete="lastname" autofocus placeholder="Lastname *">

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
                            
                                    <button type="submit" class="food-btn btn-block style-2"><span>Submit</span></button>
                                </form>
                            </div>
                            <br>
                            <hr>

                        </div>
                    </div>
                </div>
          
        </section>
        <!-- Booking Table Section Ending Here -->

@endsection
