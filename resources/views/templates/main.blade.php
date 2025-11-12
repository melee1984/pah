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
    <script src="{{ asset('js/app.js') }}" defer></script>


</head>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<!-- Scripts -->
<body >
    <!-- preloader -->
    <!-- <div class="preloader"><div class="load loade"><hr/><hr/><hr/><hr/></div></div> -->
    <!-- preloader -->
    <div id="app">
        @include('includes.nav')
        @yield('content')
        <register-form></register-form>
        <my-component></my-component>
        <user-current-location></user-current-location>
    </div>

    @include('includes.footer')
    <!-- scrollToTop start here -->
    <a href="#" class="scrollToTop"><i class="icofont-swoosh-up"></i></a>
    <!-- scrollToTop ending here -->

    @include('pages.includes.js')
</body>

</html>