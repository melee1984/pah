@extends('templates.inside')
@section('content')

 <!-- Page Header Section Start Here -->
        <section class="page-header style-2">
            <div class="container">
                <div class="page-title text-center">
                    <h3>Payment Method</h3>
                    <ul class="breadcrumb">
                        <li><a href="{{ URL::to('/') }}">Home</a></li>
                        <li>Payment Method</li>
                    </ul>
                </div>
            </div>
        </section>
        <!-- Page Header Section Ending Here -->

        
        <!-- About Section Start here -->
		<section class="about about-page padding-tb">
            <div class="container">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-12">
                        <div class="about-thumb">
                            <img src="assets/images/about/01.png" alt="about-food">
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="about-content">
                            <div class="section-header">
                                <span>Welcome Pahatud</span>
                                <h3>Ready, Set, Delivered!</h3>
                            </div>
                            <div class="section-wrapper">
                                <p>Pahatud - means to “request to deliver”.</p>
                                <p>Pahatud offers affordable errands for you! We deliver local goods and food items. We simple connects entrepreneur, restaurants, and online seller. We deliver your Item to your customer with a purpose.</p>
                                <a href="#" class="food-btn style-2"><span>get diraction</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
		<!-- About Section Ending here -->
		
        
         @include('pages.includes.sponsor')

        

       @include('pages.includes.newsletter')


@endsection