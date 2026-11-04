<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Continue merchant registration | PahatudFood</title>
    @include('agent.partials.styles')
</head>
<body class="agent-registration-success-body">
<main class="agent-registration-success-shell">
    <section class="agent-registration-success-card">
        <h1>Continue your registration</h1>
        <p>Enter the business email used in your application to receive a fresh setup link. Already chose a password? Sign in to continue your business details and documents.</p>
        @if (session('status')) <div class="agent-alert" role="status">{{ session('status') }}</div> @endif
        @if ($errors->any()) <div class="agent-alert agent-alert-error" role="alert">{{ $errors->first() }}</div> @endif
        <form method="POST" action="{{ route('merchant.register.resend-link') }}" class="agent-login-form">
            @csrf
            <div class="agent-field"><label for="email">Business email</label><input id="email" class="agent-input" name="email" type="email" maxlength="255" value="{{ old('email') }}" autocomplete="email" required></div>
            <button class="agent-button agent-button-primary" type="submit">Email me a registration link</button>
        </form>
        <div class="agent-success-actions"><a href="{{ route('merchant.login') }}">Merchant login</a><a href="{{ route('merchant.register') }}">New application</a></div>
    </section>
</main>
</body>
</html>
