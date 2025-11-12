<!-- Mobile Menu Start Here -->
<div class="mobile-menu">
	<nav class="mobile-header d-xl-none d-lg-none d-md-none">
		<div class="header-logo">  
			<a href="{{ URL::to('/') }}" class="logo"><img src="{{ asset('images/logo-small.jpg') }}" alt="logo"></a>
		</div>
		<div class="header-bar">
			<span></span>
			<span></span>
			<span></span>
		</div>
	</nav>
	<nav class="menu">
		<div class="mobile-menu-area d-xl-none d-lg-none">
			<div class="mobile-menu-area-inner scrollbar">
				<div class="mobile-search">
					<form action="{{ route('search') }}" method="post">
						<input type="text" name="s" placeholder="Enter your food name, restaurant or city">
						<button type="submit"><i class="icofont-search-2"></i></button>
					</form>
				</div>
				<ul>
	            <li><a href="{{ URL::to('/') }}">Home</a></li>
	            <li><a href="{{ route('aboutus') }}">About us</a></li>
	            <li><a href="{{ route('bepartner') }}">Be our Partner</a></li>
	            <li><a href="{{ route('restaurant.show') }}">Restaurants</a></li>
	            <li><a href="{{ route('restaurant.show') }}">Flowerstores</a></li>
				<li><a href="{{ route('new.bookings') }}">Delivery Rates</a></li>
	            
	            <li><a href="/logout"  onclick="event.preventDefault();
	            document.getElementById('logout-form').submit();">Logout</a>
	            </li>
	        </ul>
			</div>
		</div>
	</nav>
</div>
<!-- Mobile Menu Ending Here -->
<!-- header section start -->
<header class="header-section d-xl-block d-lg-block d-md-block d-none" id="header-menu">
	<div class="container-fluid">
		<div class="header-area pb-4 pt-4" >
			<div class="logo">
				<a href="{{URL::to('/')}}"><img src="{{ asset('images/logo-small.jpg') }}" alt="logo"></a>
			</div>
			<div class="author-option">
				@if (Auth::check()) 
		        <ul class="nav nav-pills">
		              <li class="nav-item dropdown">
		                <a class="nav-link dropdown-toggle" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false"><i class="icofont-ui-user"></i>
		 Hi {{ Auth::User()->firstname }}</a>
		                <div class="dropdown-menu">
		                  <a class="dropdown-item" href="{{ route('profile.dashboard') }}">Profile</a>
		                  <a class="dropdown-item" href="{{ route('profile.orders') }}">My Orders</a>

		                  <a class="dropdown-item" href="{{ route('profile.bookings') }}">My Bookings</a>
		                  <a class="dropdown-item" href="{{ route('profile.dashboard') }}">Settings</a>
		                  
		                  <div class="dropdown-divider"></div>
		                  <a class="dropdown-item" href="#"  onclick="event.preventDefault();
		                    document.getElementById('logout-form').submit();">{{ __('Logout') }}</a>
		                </div>
		              </li>
		            </ul>
		            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
		          	  @csrf
		            </form>
		        @else 
		            <span style="margin-left:8px;">
		                <a href="{{ route('profile.dashboard') }}">My Dashboard</a>
		            </span>
		        @endif
			</div>
			<!-- <div class="author-option">
				<div class="author-area">
				</div>
			</div> -->
		</div>
	</div>
</header>
<!-- header section ending -->