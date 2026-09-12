<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Agent Login | Pahatud</title>
    @include('agent.partials.styles')
</head>
<body class="agent-login-body">
<main class="agent-login-shell">
    <section class="agent-login-panel">
        <div class="agent-login-card">
            <a class="agent-login-brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Pahatud">
                <span><strong>Pahatud</strong><span>Agent Portal</span></span>
            </a>

            <h1>Welcome back</h1>
            <p class="agent-login-intro">Sign in to enroll restaurants, monitor qualifying orders, and track your commissions.</p>

            @if ($errors->any())
                <div class="agent-alert agent-alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="agent-login-form" method="POST" action="{{ route('agent.login.store') }}">
                @csrf
                <div class="agent-field">
                    <label for="email">Email address</label>
                    <input class="agent-input @error('email') agent-input-error @enderror" id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" placeholder="agent@example.com" required autofocus>
                </div>
                <div class="agent-field">
                    <label for="password">Password</label>
                    <input class="agent-input @error('password') agent-input-error @enderror" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                </div>
                <div class="agent-login-row">
                    <label class="agent-checkbox"><input name="remember" type="checkbox" value="1"> Keep me signed in</label>
                </div>
                <button class="agent-button agent-button-primary agent-login-submit" type="submit">Sign in to Agent Portal</button>
            </form>

            <p class="agent-login-help">Want to become an agent? <a class="agent-text-link" href="{{ route('agent.register') }}">Learn about the program and apply</a>.</p>
        </div>
    </section>

    <section class="agent-login-visual" aria-label="Pahatud Agent Portal introduction">
        <div class="agent-login-visual-copy">
            <span class="agent-login-kicker">Partner. Grow. Earn.</span>
            <h2>Help local restaurants move forward.</h2>
            <p>Bring great restaurants onto Pahatud and see the value you create—from their first order to every commission earned.</p>
        </div>
        <div class="agent-login-flow">
            <div><span>01</span><strong>Enroll a restaurant</strong></div>
            <div><span>02</span><strong>They complete orders</strong></div>
            <div><span>03</span><strong>You earn commission</strong></div>
        </div>
    </section>
</main>
</body>
</html>
