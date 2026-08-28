<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Application Received | Pahatud Agent Program</title>
    @include('agent.partials.styles')
</head>
<body class="agent-program-body agent-registration-success-body">
<main class="agent-registration-success-shell">
    <section class="agent-registration-success-card">
        <a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>Pahatud</strong><span>Agent Program</span></span></a>

        <div class="agent-success-mark" aria-hidden="true">✓</div>
        <p class="agent-eyebrow">Application received</p>
        <h1>Thank you for applying!</h1>
        <p class="agent-success-lead">Your Pahatud Agent application has been submitted successfully. A confirmation was sent to <strong>{{ $email }}</strong>.</p>

        <div class="agent-success-next">
            <h2>What happens next?</h2>
            <div><span>1</span><p><strong>Check your inbox</strong>Your confirmation email contains your submitted account details and starting agent share.</p></div>
            <div><span>2</span><p><strong>Application review</strong>The Pahatud operations team will review your application before enabling portal access.</p></div>
            <div><span>3</span><p><strong>Receive approval</strong>We will email you when your account is approved and ready to access.</p></div>
            <div><span>4</span><p><strong>Start enrolling</strong>Sign in to your Agent Dashboard and begin enrolling restaurant partners.</p></div>
        </div>

        <div class="agent-success-actions"><a class="agent-button agent-button-primary" href="{{ route('agent.login') }}">Go to Agent Login</a><a class="agent-button agent-button-secondary" href="{{ route('home') }}">Return to Pahatud</a></div>
        <p class="agent-success-help">You will not be able to sign in until your application has been approved.</p>
    </section>
</main>
</body>
</html>
