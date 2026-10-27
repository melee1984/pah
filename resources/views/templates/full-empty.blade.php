<!DOCTYPE html>
<html lang="en">

<head>
    @include('includes.meta', ['defaultRobots' => 'noindex, nofollow'])
    @include('includes.gtag')

</head>
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">

<!-- Scripts -->
@vite('resources/js/app.js')

<body class="landing-page">
    <div id="app">
        <support-center :authenticated="{{ auth()->check() ? 'true' : 'false' }}"></support-center>
        @include('includes.landing-header')
        @yield('content')
    </div>
</body>
@stack('scripts')

</html>
