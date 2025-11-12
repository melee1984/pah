<!DOCTYPE html>
<html lang="en">
    <head>

        @hasSection('title')
            <title>@yield('title') | Order Online from Pahatud Delivery Services</title>
        @else 
        <title>Pahatud Order Online from Pahatud Delivery Services</title>
        @endif

        @include('includes.meta')

        @include('includes.gtag')


    </head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <body>
        <!-- preloader -->
        <!-- <div class="preloader">
                <div class="load loade"><hr/><hr/><hr/><hr/></div>
        </div> -->
        <!-- preloader -->

            <div class="search-area">
                <div class="search-input">
                    <div class="search-close">
                        <span></span>
                        <span></span>
                    </div>
                    <form action="{{ route('search') }}" method="post">
                        @csrf()
                        <input type="text" name="s" placeholder="Enter your food name, restaurant or city">
                    </form>
                </div>
            </div>

             <div id="app">
                @include('includes.nav')
                <!-- search area -->
                @yield('content')

                <register-form></register-form>
                <login-form></login-form>
                <user-current-location></user-current-location>
                <summary-basket-mobile></summary-basket-mobile>
            </div>
		  @include('includes.footer')

        <!-- scrollToTop start here -->
		<a href="#" class="scrollToTop d-none d-sm-block d-sm-none d-md-block"><i class="icofont-swoosh-up"></i></a>
		<!-- scrollToTop ending here -->

		@include('pages.includes.js')

        <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
        
	</body>
</html>