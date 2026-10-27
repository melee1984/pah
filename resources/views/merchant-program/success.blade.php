<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Application Received | PahatudFood</title>
    @include('agent.partials.styles')
</head>
<body class="agent-program-body agent-registration-success-body">
<main class="agent-registration-success-shell">
    <section class="agent-registration-success-card">
        <a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>PahatudFood</strong><span>Merchant Partners</span></span></a>
        <div class="agent-success-mark" aria-hidden="true">✓</div>
        <p class="agent-eyebrow">Application received</p>
        <h1>Thanks for applying!</h1>
        <p class="agent-success-lead">Your PahatudFood merchant application was saved successfully. We sent a confirmation to <strong>{{ $email }}</strong>.</p>
        <div class="agent-success-next">
            <h2>What happens next?</h2>
            <div><span>1</span><p><strong>Initial review</strong>Our team reviews your business, contact, location, and requested service details.</p></div>
            <div><span>2</span><p><strong>Email decision</strong>We will notify you when the application is approved or declined.</p></div>
            <div><span>3</span><p><strong>Documents and onboarding</strong>If approved, you will receive instructions for business verification, account setup, menu preparation, and final activation.</p></div>
        </div>
        <div class="agent-success-actions"><a class="agent-button agent-button-primary" href="{{ route('home') }}">Return to Pahatud</a><a class="agent-button agent-button-secondary" href="{{ route('merchant.login') }}">Merchant login</a></div>
        <p class="agent-success-help">Approval of this initial application does not make the storefront live; verification and onboarding must still be completed.</p>
    </section>
</main>
</body>
</html>
