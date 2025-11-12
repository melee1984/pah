@extends('templates.inside')
@section('content')

 <!-- Page Header Section Start Here -->
        <section class="page-header style-2">
            <div class="container">
                <div class="page-title text-center">
                    <h3>Be our Partner</h3>
                    <ul class="breadcrumb">
                        <li><a href="{{ URL::to('/') }}">Home</a></li>
                        <li>Partner</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- Page Header Section Ending Here -->
        <!-- About Section Start here -->
		<section class="about about-page padding-tb">
            <div class="container">
                @include('includes.error') 

                <div class="row align-items-center">
                    <div class="col-lg-12 col-12">
                            <div class="section-header text-left">
                                <h2>Come in and Join us!</h2>
                            </div>
                            <div class="section-wrapper">
                                <p><b>We know that we love food and food is life!</b></p>
                                <p>Let us join together, when you make Food we will Deliver it to them with love!.</p>
                                <br/>
                                <h6>Partner Benefits</h6>
                                <ul>
                                   <li>Growing Consumer network </li>
                                   <li>Affordable delivery rate with options</li>
                                    <li>Partner dashboard with reports </li>
                                    <li>Delivery Partner</li>
                                    <li>Awesome Rider</li>
                                    <li>Customer Support</li>
                                    <li>Your online presence </li>
                                    <li>Mobile Application and Website  </li>
                                    <li>Payment Gateway </li>
                                    <li>Additional Revenue</li>
                                </ul>
                                <br><br>
                                <a href="{{ route('merchant.register') }}" class="btn btn-pahatud"> Click here to Register</a>

                            </div>



                    </div>
                </div>
            </div>
        </section>
		<!-- About Section Ending here -->

        <!-- Booking Table Section Start Here -->
        
        <!-- Booking Table Section Ending Here -->
        @include('pages.includes.newsletter')
@endsection