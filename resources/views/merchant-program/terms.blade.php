<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Merchant Terms and Conditions | PahatudFood</title>
    <meta name="description" content="Read the terms that apply when joining and using PahatudFood as a merchant partner.">
    @include('agent.partials.styles')
</head>
<body class="agent-program-body merchant-terms-body">
<header class="agent-program-nav merchant-terms-nav">
    <a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>PahatudFood</strong><span>Merchant Partners</span></span></a>
    <a class="agent-button agent-button-secondary" href="{{ route('merchant.register') }}#apply">Back to application</a>
</header>

<main class="merchant-terms-shell">
    <article class="merchant-terms-card">
        <header class="merchant-terms-heading">
            <p class="agent-eyebrow">Merchant partnership</p>
            <h1>Merchant Terms and Conditions</h1>
            <p>These terms explain the basic rules for applying to become, and operating as, a PahatudFood merchant. We use simple language so you can understand what you are agreeing to.</p>
        </header>

        <section>
            <h2>1. Applying to PahatudFood</h2>
            <p>You confirm that you are allowed to apply for the business and that the information you provide is complete and accurate. Sending an application does not guarantee approval. Your store will not appear on PahatudFood until we finish our review, verify the required documents, and activate your account.</p>
        </section>

        <section>
            <h2>2. Your responsibilities</h2>
            <p>You are responsible for keeping your business permits, registrations, tax information, and other required documents valid and up to date. You must follow food safety, consumer protection, employment, privacy, and other laws that apply to your business.</p>
            <p>You must keep your store details, menu, prices, availability, operating hours, and preparation times accurate. You are responsible for the quality, safety, packaging, and correct preparation of every order you accept.</p>
        </section>

        <section>
            <h2>3. Orders and customer service</h2>
            <p>You agree to review incoming orders promptly and fulfill accepted orders as shown in the merchant dashboard. If an item is unavailable or an order cannot be completed, you must update the order or contact PahatudFood as soon as possible. Repeated cancellations, serious delays, unsafe food, or misleading information may lead to account restrictions or suspension.</p>
        </section>

        <section>
            <h2>4. Fees and payments</h2>
            <p>The commission rate, payout schedule, deductions, refunds, and any other charges that apply to your business will be explained during onboarding or in a separate commercial agreement. You agree to review and accept those details before your store is activated. You are responsible for your own taxes unless the law requires PahatudFood to collect or withhold them.</p>
        </section>

        <section>
            <h2>5. Menu content and business information</h2>
            <p>You allow PahatudFood to display the business name, logo, photos, menu, prices, descriptions, and other information you provide for operating and promoting the service. You confirm that you own this content or have permission to use it. You must not upload false, illegal, offensive, or rights-infringing content.</p>
        </section>

        <section>
            <h2>6. Customer data and account security</h2>
            <p>Use customer information only to prepare, fulfill, and support PahatudFood orders. Do not sell it, use it for unrelated marketing, or share it unless the law allows or requires you to do so. Keep your login details secure and tell PahatudFood promptly if you believe someone has accessed your account without permission.</p>
        </section>

        <section>
            <h2>7. Delivery and third-party services</h2>
            <p>Available delivery, pickup, and dine-in features may depend on your location and the services offered by PahatudFood or its partners. Each party is responsible for the parts of an order it controls. Any additional service rules will be shared with you during onboarding.</p>
        </section>

        <section>
            <h2>8. Account review, suspension, or closure</h2>
            <p>PahatudFood may review, limit, suspend, or close a merchant account when needed to protect customers, comply with the law, investigate suspected fraud, address unpaid amounts, or respond to serious or repeated rule violations. When practical, we will explain the reason and what you can do next. You may ask to close your merchant account after completing outstanding orders and obligations.</p>
        </section>

        <section>
            <h2>9. Service availability and responsibility</h2>
            <p>We work to keep PahatudFood reliable, but the service may sometimes be interrupted for maintenance, technical problems, or events outside our control. Each party remains responsible for its own actions, staff, products, legal duties, and losses caused by its negligence or misconduct. Nothing in these terms removes rights or responsibilities that cannot legally be removed.</p>
        </section>

        <section>
            <h2>10. Changes to these terms</h2>
            <p>We may update these terms when our services or legal requirements change. If a change is important, we will provide reasonable notice through the merchant dashboard, email, or another appropriate channel. Continuing to use the merchant service after the change takes effect means you accept the updated terms.</p>
        </section>

        <section>
            <h2>11. Questions</h2>
            <p>If you have a question about these terms, contact Pahatud before submitting your application. You can use the <a class="agent-text-link" href="{{ route('contactus') }}">contact page</a> to reach us.</p>
        </section>

        <div class="merchant-terms-action">
            <a class="agent-button agent-button-primary" href="{{ route('merchant.register') }}#apply">Return to the application</a>
        </div>
    </article>
</main>

<footer class="agent-program-footer"><a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>PahatudFood</strong><span>Merchant Partners</span></span></a><p>Helping local food businesses connect, serve, and grow.</p><span>© {{ date('Y') }} Pahatud. All rights reserved.</span></footer>
</body>
</html>
