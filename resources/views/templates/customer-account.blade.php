<!DOCTYPE html>
<html lang="en">
<head>
    @include('includes.meta')
    <title>@yield('title', 'My Dashboard') | PahatudFood</title>
    @include('includes.gtag')
    @vite('resources/js/app.js')
</head>
<body class="landing-page customer-account-page">
    @include('includes.landing-header')
    <div id="app">
        <main class="customer-account-main container">
            <div class="customer-account-heading"><span class="public-page-kicker">Your PahatudFood account</span><h1>@yield('title', 'My Dashboard')</h1></div>
            <nav class="customer-account-tabs" aria-label="Customer dashboard pages">
                @foreach (['profile.dashboard' => 'Dashboard', 'profile.orders' => 'My Orders', 'profile.edit' => 'My Profile', 'profile.support' => 'My Support Requests'] as $route => $label)
                    <a href="{{ route($route) }}" @if(request()->routeIs($route) || ($route === 'profile.orders' && request()->routeIs('profile.orders.view'))) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            @if(session('success'))<p class="customer-success" role="status">{{ session('success') }}</p>@endif
            @yield('content')
        </main>
        @unless(request()->routeIs('profile.support'))<support-center authenticated></support-center>@endunless
    </div>
    @include('includes.footer')
    @include('pages.includes.js')
</body>
</html>
