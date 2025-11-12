@extends('templates.main')
@section('content')

@include('pages.includes.banner')

<!-- Banner Section Start Here -->
<!-- <section class="banner">
	<div class="shape-1">
		<a href="https://play.google.com/store/apps/details?id=io.pahatud.com" style="cursor: pointer;">
			<img src="{{ asset('images/banner-app.png') }}" alt="banner">
		</a>
	</div>
	<div class="shape-2">
		<img src="{{ asset('images/banner-left.png') }}" alt="banner">
	</div>
	<div class="banner-area">
		<div class="container">
			<div class="row">
				<div class="col-xl-8 col-12">
					<div class="banner-content">
						<h2>Unique Food Network...</h2>
						<form action="{{ route('search') }}" method="post">
							@csrf()
							<div class="codexcoder-selectoption">
								<select name="o">
									<option value="1">Find A Food</option>
								</select>
							</div>
							<input type="text" name="s" placeholder="Enter your food name, restaurant or city">
							<button type="submit"><span class="material-icons">search</span></button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section> -->
<!-- Banner Section Ending Here -->
<!-- Food Catagory Section Start here -->
<section class="food-category padding-tb" style="background-image: url(assets/css/bg-image/category-bg.jpg); background-size: cover;">
	<div class="container">
		<div class="food-box">
			<div class="section-header">
				<h3>Browse Food Category</h3>
				<p>We provide variety of food category selection for your craving</p>
			</div>
			<div class="section-wrapper">
				<div class="food-slider">
					<div class="swiper-wrapper">
						@foreach($sectors as $sector)
						<div class="swiper-slide">
							<div class="food-item">
								<div class="food-thumb">
									<a href="{{ URL::to('restaurants/'.strtolower($sector->name)) }}"><img src="images/icons/{{ $sector->img }}" alt="food"></a>
								</div>
								<div class="food-content">
									<a href="{{ URL::to('restaurants/'.strtolower($sector->name)) }}">{{ $sector->name }}</a>
								</div>
							</div>
						</div>
						@endforeach
				</div>
				<div class="food-slider-next"><i class="icofont-double-left"></i></div>
				<div class="food-slider-prev"><i class="icofont-double-right"></i>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- Food Catagory Section Ending here -->

<!-- Food Services Section Start here -->
<section class="food-services padding-tb">
	<div class="container">
		<div class="section-header">
			<h3>How it Works</h3>
			<p>We simplify the process and make it easy for you</p>
		</div>
		<div class="section-wrapper">
			<div class="service-item">
				<div class="service-inner">
					<div class="service-thumb">
						<img src="images/step1.png" alt="food-service">
						<span>01 step</span>
					</div>
					<div class="service-content">
						<h6><a href="#">Choose Your Favorite</a></h6>
					</div>
				</div>
			</div>
			<div class="service-item">
				<div class="service-inner">
					<div class="service-thumb">
						<img src="images/step2.png" alt="food-service">
						<span>02 step</span>
					</div>
					<div class="service-content">
						<h6><a href="#">We Deliver Your Meals</a></h6>
					</div>
				</div>
			</div>
			<div class="service-item">
				<div class="service-inner">
					<div class="service-thumb">
						<img src="images/step3.png" alt="food-service">
						<span>03 step</span>
					</div>
					<div class="service-content">
						<h6><a href="#">Cash on Delivery</a></h6>
					</div>
				</div>
			</div>
			<div class="service-item">
				<div class="service-inner">
					<div class="service-thumb">
						<img src="images/step4.png" alt="food-service">
						<span>04 step</span>
					</div>
					<div class="service-content">
						<h6><a href="#">Eat And Enjoy</a></h6>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>

<!-- Food Services Section Ending here -->
<!-- pages.includes.mostpopular -->
<!-- Food Apps Section Start here -->
<!-- pages.includes.mobileads -->
<!-- Popular Food Section Style 2 Start Here -->
@include('pages.includes.contact')
<!-- pages.includes.weeklyfoods -->
<!-- Partners -->
@include('pages.includes.partner')
<!-- End of Partners -->

@endsection