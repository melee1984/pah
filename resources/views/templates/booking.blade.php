<!DOCTYPE html>
<html lang="en">
    <head>
        
        @include('includes.meta')

        @hasSection('title')
            <title>@yield('title') | Pahatud Delivery Services</title>
        @else 
        <title>Pahatud Delivery Services</title>
        @endif

        @include('includes.gtag')
        
    </head>
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
    <link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    <body>
        <!-- preloader -->
        <!-- <div class="preloader"><div class="load loade"><hr/><hr/><hr/><hr/></div></div> -->
        <!-- preloader -->
         <!-- search area -->
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
        <!-- search area -->
        <div id="app">
    		@include('includes.nav2')
    		<!-- search area -->
            @yield('content')
           <register-form></register-form>
           <login-form></login-form>
         </div>
		@include('includes.footer')
        <!-- scrollToTop start here -->
		<a href="#" class="scrollToTop"><i class="icofont-swoosh-up"></i></a>
		<!-- scrollToTop ending here -->

		@include('pages.includes.js')

        <script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
        
	</body>
</html>