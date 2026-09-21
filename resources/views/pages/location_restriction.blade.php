@extends('templates.full-empty')

@section('content')

 <!-- Page Header Section Start Here -->
    <section class="page-header style-2">
        <div class="container">
            <div class="page-title text-center">
                <h3>Privacy Policy</h3>
                <ul class="breadcrumb">
                    <li><a href="{{ URL::to('/') }}">Home</a></li>
                    <li>Location</li>
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
                     <div class="col-lg-12 col-12">
                        <div class="section-wrapper">
                            <h6>Location </h6>
                            <p>We use your location to show nearby options and deliver personalized content. We were unable to access your coordinates. Please enable location services and refresh the page.</p>
                            <get-current-location></get-current-location>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
	<!-- About Section Ending here -->
   	@include('pages.includes.newsletter')
@endsection