@extends('templates.inside')
@section('content')

 <!-- Page Header Section Start Here -->
        <section class="page-header style-2">
                <div class="page-title text-center">
                    <h3>About Our Pahatud</h3>
                    <ul class="breadcrumb">
                        <li><a href="{{ URL::to('/') }}">Home</a></li>
                        <li>About us</li>
                    </ul>
                </div>
            </div>
        </section>   <div class="container">
         
        <!-- Page Header Section Ending Here -->
        <!-- About Section Start here -->
		<section class="about about-page padding-tb">
            <div class="container">
            	@include('includes.error')     
                <div class="row align-items-center">
                    <div class="col-lg-6 col-12">
                        <div class="about-thumb">
                            <img src="images/logo-big.png" alt="about-food">
                        </div>
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="about-content">
                            <div class="section-header">
                                <span>Welcome Pahatud</span>
                                <h6>Ready, Set, Delivered!</h6>
                            </div>
                            <div class="section-wrapper">
                                <p>Pahatud - means to “request to deliver”.</p>
                                <p>Pahatud offers affordable errands for you! We deliver local goods and food items. We simple connects entrepreneur, restaurants, and online seller. We deliver your Item to your customer with a purpose.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
		<!-- About Section Ending here -->
		
		<!-- Testimonial Section Start Here -->
		<!-- <section class="testimonial padding-tb" style="background-image: url(assets/css/bg-image/category-bg.jpg); background-size: cover;">
			<div class="container">
				<div class="section-wrapper">
					<div class="quete-thumb">
						<img src="assets/images/testimonial/icon/01.jpg" alt="food-quete">
					</div>
					<div id="demo" class="carousel slide vert">
						<div class="carousel-inner">
							<div class="carousel-item active">
								<div class="testi-item">
									<p>Extend Accurate Services  Long Term High Impact Experiences Interactiv Streamline Team Compelingly Simplify Solutions Before Technicaly Sound Leadership Skills Creative Holstic Process Improvements Proactively Streamline Alternative Niche Markets Forwor Resource Conveniently cultivate pandemic technology and corporate.</p>
									<h6>Somrat Islam <span>(UI Designer)</span></h6>
									<div class="rating">
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
									</div>
								</div>
							</div>
							<div class="carousel-item">
								<div class="testi-item">
									<p>Extend Accurate Services  Long Term High Impact Experiences Interactiv Streamline Team Compelingly Simplify Solutions Before Technicaly Sound Leadership Skills Creative Holstic Process Improvements Proactively Streamline Alternative Niche Markets Forwor Resource Conveniently cultivate pandemic technology and corporate.</p>
									<h6>Somrat Islam <span>(UI Designer)</span></h6>
									<div class="rating">
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
									</div>
								</div>
							</div>
							<div class="carousel-item">
								<div class="testi-item">
									<p>Extend Accurate Services  Long Term High Impact Experiences Interactiv Streamline Team Compelingly Simplify Solutions Before Technicaly Sound Leadership Skills Creative Holstic Process Improvements Proactively Streamline Alternative Niche Markets Forwor Resource Conveniently cultivate pandemic technology and corporate.</p>
									<h6>Somrat Islam <span>(UI Designer)</span></h6>
									<div class="rating">
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
										<i class="icofont-star"></i>
									</div>
								</div>
							</div>
						</div>
						<div class="carousel-indicators">
							<div data-target="#demo" data-slide-to="0" class="item active">
								<img src="assets/images/testimonial/01.jpg" alt="">
							</div>
							<div data-target="#demo" data-slide-to="1" class="item">
								<img src="assets/images/testimonial/02.jpg" alt="">
							</div>
							<div data-target="#demo" data-slide-to="2" class="item">
								<img src="assets/images/testimonial/03.jpg" alt="">
							</div>
						</div>
					</div>
				</div>
			</div>
		</section> -->
		<!-- Testimonial Section Ending Here -->
        
		@include('pages.includes.sponsor')
		@include('pages.includes.newsletter')

@endsection