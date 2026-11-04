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
        <h1>Check your email to continue</h1>
        <p class="agent-success-lead">Your application was saved successfully.
            @if ($mailSent) We sent instructions and a secure registration link to <strong>{{ $email }}</strong>. Check your inbox and spam folder.
            @else We could not send your registration email to <strong>{{ $email }}</strong> right now. Your application is saved; request a new link below without submitting it again. @endif</p>
        <div class="agent-success-next">
            <h2>What happens next?</h2>
            <div><span>1</span><p><strong>Choose your password</strong>Open the registration link in your email to secure your account.</p></div>
            <div><span>2</span><p><strong>Complete your details and documents</strong>After account setup, you will go directly to your application to finish the missing information and upload documents.</p></div>
            <div><span>3</span><p><strong>Verification and activation</strong>Our team reviews your application and documents and emails you updates. Your storefront stays inactive until verification.</p></div>
        </div>
        <div class="agent-success-actions"><a class="agent-button agent-button-primary" href="{{ route('merchant.register.request-link') }}">Request a new registration link</a><a class="agent-button agent-button-secondary" href="{{ route('merchant.login') }}">Merchant login</a></div>
        <p class="agent-success-help">Approval of this initial application does not make the storefront live; verification and onboarding must still be completed.</p>
    </section>
</main>
</body>
</html>
