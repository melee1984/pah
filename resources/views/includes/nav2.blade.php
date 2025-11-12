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
					<form action="{{ URL::to('restaurants') }}" method="get">
						<input type="text" name="search" placeholder="Search Here.........">
						<button type="submit"><i class="icofont-search-2"></i></button>
					</form>
				</div>
				<menu-list></menu-list>
			</div>
		</div>
	</nav>
</div>
<!-- Mobile Menu Ending Here -->
<!-- header section start -->
<header class="header-section d-xl-block d-lg-block d-md-block d-none" id="header-menu">
	<div class="container-fluid">
		<div class="header-area">
			<div class="logo">
				<a href="{{URL::to('/')}}"><img src="{{ asset('images/logo-small.jpg') }}" alt="logo"></a>
			</div>
			<div class="main-menu">
				
			</div>
			<div class="author-option">
				<div class="author-area">
					@include('includes.sidemenu')
				</div>
			</div>
		</div>
	</div>
</header>
<!-- header section ending -->