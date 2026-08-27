<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>@yield('title', 'Agent Portal') | Pahatud</title>
    @include('agent.partials.styles')
</head>
<body class="agent-body">
<div class="agent-shell">
    <aside class="agent-sidebar">
        <a class="agent-brand" href="{{ route('agent.dashboard') }}">
            <img src="{{ asset('images/logo.jpg') }}" alt="Pahatud">
            <span class="agent-brand-copy"><strong>Pahatud</strong><span>Agent Portal</span></span>
        </a>

        <p class="agent-nav-label">Workspace</p>
        <nav class="agent-nav" aria-label="Agent navigation">
            <a class="agent-nav-link {{ request()->routeIs('agent.dashboard') ? 'active' : '' }}" href="{{ route('agent.dashboard') }}">
                <span class="agent-nav-icon">⌂</span><span>Dashboard</span>
            </a>
            <a class="agent-nav-link {{ request()->routeIs('agent.restaurants.index') ? 'active' : '' }}" href="{{ route('agent.restaurants.index') }}">
                <span class="agent-nav-icon">▦</span><span>Restaurants</span>
            </a>
            <a class="agent-nav-link {{ request()->routeIs('agent.reports.*') ? 'active' : '' }}" href="{{ route('agent.reports.index') }}">
                <span class="agent-nav-icon">↗</span><span>Reports</span>
            </a>
            <a class="agent-nav-link {{ request()->routeIs('agent.restaurants.create') ? 'active' : '' }}" href="{{ route('agent.restaurants.create') }}">
                <span class="agent-nav-icon">＋</span><span>Enroll</span>
            </a>
        </nav>

        <div class="agent-sidebar-footer">
            <form method="POST" action="{{ route('agent.logout') }}">
                @csrf
                <button class="agent-nav-link agent-logout" type="submit"><span class="agent-nav-icon">↪</span><span>Sign out</span></button>
            </form>
        </div>
    </aside>

    <main class="agent-main">
        <header class="agent-topbar">
            <span class="agent-mobile-brand">Pahatud <span style="color:#ef3b35">Agent</span></span>
            <span class="agent-topbar-note">Grow local businesses. Earn on every qualifying order.</span>
            <div class="agent-profile">
                <span class="agent-avatar">{{ mb_strtoupper(mb_substr(auth('agent')->user()->name, 0, 1)) }}</span>
                <div><strong>{{ auth('agent')->user()->name }}</strong><span>{{ number_format(auth('agent')->user()->commission_percentage, 2) }}% commission</span></div>
            </div>
        </header>

        <div class="agent-content">
            @if (session('success'))
                <div class="agent-alert agent-alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="agent-alert agent-alert-warning" role="alert">{{ session('warning') }}</div>
            @endif
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
