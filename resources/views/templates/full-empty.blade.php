<!DOCTYPE html>
<html lang="en">

<head>
    @include('includes.meta')
    <title>@yield('title') | Pahatud</title>
    @include('includes.gtag')

</head>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

<!-- Scripts -->
@vite('resources/js/app.js')

<body>
    <div id="app">
        <div class="container-fluid">
            @include('includes.nav-logo')
        </div>
        @yield('content')
    </div>
</body>
@stack('scripts')

</html>