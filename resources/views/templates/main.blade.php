<!DOCTYPE html>
<html lang="en">
<head>
    @include('includes.meta')
    @include('includes.gtag')
    @vite(['resources/js/app.js'])

</head>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<!-- Scripts -->
<body class="landing-page">
    <!-- preloader -->
    <!-- <div class="preloader"><div class="load loade"><hr/><hr/><hr/><hr/></div></div> -->
    <!-- preloader -->
    <div id="app">
        <support-center :authenticated="{{ auth()->check() ? 'true' : 'false' }}"></support-center>
        @include('includes.landing-header')
        @yield('content')
        <register-form></register-form>
        <user-current-location></user-current-location>
    </div>

    @include('includes.footer')
    <!-- scrollToTop start here -->
    <a href="#" class="scrollToTop"><i class="icofont-swoosh-up"></i></a>
    <!-- scrollToTop ending here -->

    @include('pages.includes.js')
</body>

</html>
