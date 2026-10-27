<!DOCTYPE html>
<html lang="en">
<head>
    @include('includes.meta', ['defaultRobots' => 'noindex, nofollow'])
    @include('includes.gtag')
    @vite('resources/js/app.js')
</head>
<body class="landing-page customer-auth-page">
    @include('includes.landing-header')
    <main>@yield('content')</main>
    <div id="app">
        <support-center :authenticated="{{ auth()->check() ? 'true' : 'false' }}"></support-center>
    </div>
    @include('includes.footer')
    @include('pages.includes.js')
</body>
</html>
