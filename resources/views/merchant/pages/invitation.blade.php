<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Complete Restaurant Account | Pahatud</title>
    @include('agent.partials.styles')
</head>
<body class="agent-login-body">
@php
    $savedName = $invitation->user->name ?? '';
    $savedFirstName = $invitation->user->firstname ?? Str::before($savedName, ' ');
    $savedLastName = $invitation->user->lastname ?? Str::after($savedName, ' ');
@endphp
<main class="agent-login-shell">
    <section class="agent-login-panel">
        <div class="agent-login-card">
            <a class="agent-login-brand" href="{{ route('home') }}">
                <img src="{{ asset('images/logo.jpg') }}" alt="Pahatud">
                <span><strong>Pahatud</strong><span>Merchant Partner</span></span>
            </a>

            <p class="agent-eyebrow">Account invitation</p>
            <h1>Complete your setup</h1>
            <p class="agent-login-intro">Create the login for <strong>{{ $invitation->restaurant->restaurant_name }}</strong>. Your restaurant will remain under review until Pahatud activates it.</p>

            @if ($errors->any())
                <div class="agent-alert agent-alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <form class="agent-login-form" method="POST" action="{{ route('restaurant.invitation.update', $token) }}">
                @csrf
                <div class="agent-form-grid">
                    <div class="agent-field">
                        <label for="firstname">First name</label>
                        <input class="agent-input" id="firstname" name="firstname" value="{{ old('firstname', $savedFirstName) }}" maxlength="75" required>
                    </div>
                    <div class="agent-field">
                        <label for="lastname">Last name</label>
                        <input class="agent-input" id="lastname" name="lastname" value="{{ old('lastname', $savedLastName) }}" maxlength="75" required>
                    </div>
                </div>
                <div class="agent-field">
                    <label for="email">Email address</label>
                    <input class="agent-input" id="email" type="email" value="{{ $invitation->email }}" readonly>
                </div>
                <div class="agent-field">
                    <label for="mobile">Mobile number</label>
                    <input class="agent-input" id="mobile" name="mobile" value="{{ old('mobile', $invitation->restaurant->mobile) }}" maxlength="30" required>
                </div>
                <div class="agent-field">
                    <label for="password">Create password</label>
                    <input class="agent-input" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <div class="agent-field">
                    <label for="password_confirmation">Confirm password</label>
                    <input class="agent-input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required>
                </div>
                <button class="agent-button agent-button-primary agent-login-submit" type="submit">Complete account setup</button>
            </form>

            <p class="agent-login-help">This invitation expires {{ $invitation->expires_at->diffForHumans() }} and can only be used once.</p>
        </div>
    </section>

    <section class="agent-login-visual" aria-label="Pahatud merchant introduction">
        <div class="agent-login-visual-copy">
            <span class="agent-login-kicker">Your restaurant, delivered</span>
            <h2>Let’s bring your menu to more customers.</h2>
            <p>Finish setting up your secure merchant account, then the Pahatud team will guide you through restaurant activation.</p>
        </div>
        <div class="agent-login-flow">
            <div><span>01</span><strong>Secure your account</strong></div>
            <div><span>02</span><strong>Complete restaurant review</strong></div>
            <div><span>03</span><strong>Start receiving orders</strong></div>
        </div>
    </section>
</main>
</body>
</html>
