<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Change Temporary Password | Pahatud</title>
    @include('agent.partials.styles')
</head>
<body class="agent-login-body">
<main class="agent-login-shell">
    <section class="agent-login-panel">
        <div class="agent-login-card">
            <div class="agent-login-brand">
                <img src="{{ asset('images/logo.jpg') }}" alt="Pahatud">
                <span><strong>Pahatud</strong><span>Agent Portal</span></span>
            </div>

            <p class="agent-eyebrow">First sign-in security</p>
            <h1>Create your password</h1>
            <p class="agent-login-intro">Replace the temporary password from your invitation email before accessing agent information.</p>

            @if ($errors->any())
                <div class="agent-alert agent-alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="agent-login-form" method="POST" action="{{ route('agent.password.update') }}">
                @csrf
                <div class="agent-field">
                    <label for="current_password">Temporary password</label>
                    <input class="agent-input" id="current_password" name="current_password" type="password" autocomplete="current-password" required autofocus>
                </div>
                <div class="agent-field">
                    <label for="password">New password</label>
                    <input class="agent-input" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="agent-field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input class="agent-input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <p class="agent-login-help" style="margin:0;padding:0;border:0">Use at least 8 characters and choose a password different from the temporary one.</p>
                <button class="agent-button agent-button-primary agent-login-submit" type="submit">Save password and continue</button>
            </form>

            <form method="POST" action="{{ route('agent.logout') }}" style="margin-top:18px;text-align:center">
                @csrf
                <button type="submit" style="background:none;border:0;color:#6a7377;cursor:pointer;font-size:13px">Sign out instead</button>
            </form>
        </div>
    </section>

    <section class="agent-login-visual" aria-label="Account security information">
        <div class="agent-login-visual-copy">
            <span class="agent-login-kicker">Secure by design</span>
            <h2>Your agent data deserves a private password.</h2>
            <p>The temporary password works only for initial access. Once replaced, it can no longer be used to sign in.</p>
        </div>
        <div class="agent-login-flow">
            <div><span>01</span><strong>Confirm temporary password</strong></div>
            <div><span>02</span><strong>Choose a private password</strong></div>
            <div><span>03</span><strong>Enter your dashboard</strong></div>
        </div>
    </section>
</main>
</body>
</html>
