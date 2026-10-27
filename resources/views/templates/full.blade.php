<!DOCTYPE html>
<html lang="en">

<head>
	@include('includes.meta')
	<title>Pahatud - Delivery Services</title>
	@include('includes.gtag')
</head>
<!-- Scripts -->
@vite('resources/js/app.js')

<body class="landing-page">
    @include('includes.landing-header')
	<!-- preloader -->
	<!-- preloader -->
	<br>
	<div id="app">
        <support-center :authenticated="{{ auth()->check() ? 'true' : 'false' }}"></support-center>
    </div>
    <div class="container">
		<div class="row justify-content-center">
			<a href="{{ URL::to('/') }}" class="logo"><img src="{{ asset('images/logo-small.jpg') }}" alt="logo"></a>
		</div>

		@yield('content')
	</div>

	<!-- scrollToTop start here -->
	<!-- scrollToTop ending here -->
	@include('pages.includes.js')
</body>

</html>