<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Become a Pahatud Agent</title>
    <meta name="description" content="Join the Pahatud Agent Program, enroll local restaurants, and earn commission from successful orders.">
    @include('agent.partials.styles')
</head>
<body class="agent-program-body">
<header class="agent-program-nav">
    <a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>Pahatud</strong><span>Agent Program</span></span></a>
    <nav aria-label="Agent program navigation"><a href="#how-it-works">How it works</a><a href="#earnings">Earnings</a><a href="#faq">FAQ</a><a class="agent-button agent-button-secondary" href="{{ route('agent.login') }}">Agent login</a><a class="agent-button agent-button-primary" href="#apply">Apply now</a></nav>
</header>

<main>
    <section class="agent-program-hero">
        <div class="agent-program-hero-copy">
            <p class="agent-eyebrow">Partner. Grow. Earn.</p>
            <h1>Help local restaurants grow—and earn when they succeed.</h1>
            <p class="agent-program-lead">Introduce restaurants to Pahatud, guide them through enrollment, and track the successful orders that generate your commission in one dedicated Agent Dashboard.</p>
            <div class="agent-program-actions"><a class="agent-button agent-button-primary" href="#apply">Start your application</a><a class="agent-button agent-button-secondary" href="#earnings">See how earnings work</a></div>
            <div class="agent-program-trust"><span>No application fee</span><span>Transparent commission ledger</span><span>Local restaurant impact</span></div>
        </div>
        <aside class="agent-earning-preview" aria-label="Commission example">
            <span class="agent-earning-label">Current agent share</span>
            <strong>{{ number_format($commissionPercentage, 2) }}%</strong>
            <p>of Pahatud's commission from each successful order placed with a restaurant you enrolled.</p>
            <div class="agent-earning-equation"><span>₱1,000 qualifying order</span><b>Pahatud: {{ number_format($pahatudCommissionPercentage, 0) }}% = ₱{{ number_format(1000 * ($pahatudCommissionPercentage / 100), 2) }}</b><strong>Agent: {{ number_format($commissionPercentage, 0) }}% = ₱{{ number_format(1000 * ($pahatudCommissionPercentage / 100) * ($commissionPercentage / 100), 2) }}</strong></div>
            <small>At the default rates, the agent's effective earnings equal {{ number_format($pahatudCommissionPercentage * ($commissionPercentage / 100), 2) }}% of the eligible order value.</small>
        </aside>
    </section>

    <section class="agent-program-section agent-benefit-section">
        <div class="agent-program-heading"><p class="agent-eyebrow">Built for growth</p><h2>What you get as a Pahatud agent</h2><p>A clear way to grow a restaurant network and see the value it creates.</p></div>
        <div class="agent-benefit-grid">
            <article><span>01</span><h3>Commission opportunities</h3><p>Earn {{ number_format($commissionPercentage, 0) }}% of Pahatud's commission from qualifying successful orders placed with restaurants assigned to you.</p></article>
            <article><span>02</span><h3>Your restaurant network</h3><p>Enroll restaurants from your portal and monitor every partner connected to your agent account.</p></article>
            <article><span>03</span><h3>Order visibility</h3><p>See submitted restaurant orders and the sales amount connected to your commission activity.</p></article>
            <article><span>04</span><h3>Transparent reporting</h3><p>Review pending, approved, paid, or reversed commissions with restaurant and order references.</p></article>
        </div>
    </section>

    <section class="agent-program-section agent-program-steps" id="how-it-works">
        <div class="agent-program-heading"><p class="agent-eyebrow">Simple process</p><h2>How the Agent Program works</h2></div>
        <div class="agent-step-grid">
            <article><span>1</span><div><h3>Apply for an account</h3><p>Tell us who you are and create a secure password. Your application is reviewed before portal access is enabled.</p></div></article>
            <article><span>2</span><div><h3>Enroll a restaurant</h3><p>Once approved, add a restaurant in your dashboard. The restaurant receives a private invitation to complete its account.</p></div></article>
            <article><span>3</span><div><h3>The restaurant starts selling</h3><p>The restaurant completes onboarding and receives orders through the Pahatud marketplace.</p></div></article>
            <article><span>4</span><div><h3>You earn on successful orders</h3><p>When an eligible restaurant order is delivered, the system records your commission automatically.</p></div></article>
            <article><span>5</span><div><h3>Track and receive payout</h3><p>Follow each entry in your commission ledger. Pahatud operations reviews commissions and coordinates approved payouts.</p></div></article>
        </div>
    </section>

    <section class="agent-program-section agent-commission-section" id="earnings">
        <div class="agent-program-heading"><p class="agent-eyebrow">Clear calculations</p><h2>Understand every peso you earn</h2><p>Pahatud first receives {{ number_format($pahatudCommissionPercentage, 2) }}% of the eligible order value. Your agent share is then calculated from Pahatud's commission—not from the full order total.</p></div>
        <div class="agent-commission-layout">
            <div class="agent-formula-card"><span>Commission formula</span><strong>Order value × Pahatud rate × Agent share = Agent commission</strong><p>Example: ₱1,000 × {{ number_format($pahatudCommissionPercentage, 0) }}% × {{ number_format($commissionPercentage, 0) }}% = ₱{{ number_format(1000 * ($pahatudCommissionPercentage / 100) * ($commissionPercentage / 100), 2) }}. Cancelled, refunded, or failed orders do not qualify.</p></div>
            <div class="agent-example-table">
                <div class="agent-example-row agent-example-head"><span>Order</span><span>Pahatud earns</span><span>Agent share</span><span>You earn</span></div>
                @foreach ([500, 1000, 2500] as $amount)
                    <div class="agent-example-row"><span>₱{{ number_format($amount, 2) }}</span><span>₱{{ number_format($amount * ($pahatudCommissionPercentage / 100), 2) }}</span><span>{{ number_format($commissionPercentage, 2) }}%</span><strong>₱{{ number_format($amount * ($pahatudCommissionPercentage / 100) * ($commissionPercentage / 100), 2) }}</strong></div>
                @endforeach
            </div>
        </div>
        <p class="agent-program-disclaimer">Examples use Pahatud's {{ number_format($pahatudCommissionPercentage, 2) }}% commission and the default agent share of {{ number_format($commissionPercentage, 2) }}%. This produces an effective agent earning of {{ number_format($pahatudCommissionPercentage * ($commissionPercentage / 100), 2) }}% of the eligible order value. Your confirmed share is shown in the Agent Dashboard and stored with every commission entry.</p>
    </section>

    <section class="agent-program-section agent-payout-section">
        <div class="agent-program-heading"><p class="agent-eyebrow">From order to payout</p><h2>A ledger you can follow</h2></div>
        <div class="agent-payout-track">
            <div><span>Pending</span><p>A successful order creates a commission entry for review.</p></div>
            <div><span>Approved</span><p>Pahatud confirms the entry is eligible for payout.</p></div>
            <div><span>Paid</span><p>Operations coordinates payment and records the commission as paid.</p></div>
        </div>
        <div class="agent-program-note">Payout timing and payment details are confirmed with Pahatud operations. The current portal provides commission tracking; it does not initiate self-service withdrawals.</div>
    </section>

    <section class="agent-program-section agent-faq-section" id="faq">
        <div class="agent-program-heading"><p class="agent-eyebrow">Questions, answered</p><h2>Frequently asked questions</h2></div>
        <div class="agent-faq-list">
            <details open><summary>When do I start earning commission?</summary><p>After your account is approved and a restaurant you enrolled completes a qualifying delivered order. Applications and restaurant enrollments alone do not generate commission.</p></details>
            <details><summary>Is the commission based on every restaurant order?</summary><p>It applies only to qualifying successful orders from restaurants assigned to your agent account. Cancelled, refunded, failed, or reversed orders are excluded.</p></details>
            <details><summary>Can my agent share change?</summary><p>Yes. Pahatud assigns your percentage share of Pahatud's commission and may change it for future qualifying orders. Each recorded commission keeps the agent share used for that specific order.</p></details>
            <details><summary>How does restaurant enrollment work?</summary><p>You submit the restaurant details from your approved Agent Dashboard. The restaurant receives a private invitation, creates its merchant login, and remains under review until activated.</p></details>
            <details><summary>What can I track in the Agent Dashboard?</summary><p>Your restaurants, their submitted orders, qualifying sales, total and monthly commission, and individual ledger entries with pending, approved, paid, or reversed status.</p></details>
            <details><summary>Can I withdraw money directly from the dashboard?</summary><p>Not currently. The dashboard tracks your commission balance and status. Pahatud operations coordinates approved payouts and marks entries as paid.</p></details>
        </div>
    </section>

    <section class="agent-program-apply" id="apply">
        <div class="agent-apply-copy"><p class="agent-eyebrow">Join the network</p><h2>Apply to become a Pahatud agent</h2><p>Create your account application below. We will confirm receipt by email, review your details, and email you again when your Agent Dashboard access is approved.</p><ol><li>Submit your application</li><li>Check your email confirmation</li><li>Wait for Pahatud approval</li><li>Sign in and enroll restaurants</li></ol></div>
        <div class="agent-application-card">
            @if (session('success'))
                <div class="agent-alert agent-alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="agent-alert agent-alert-error" role="alert">Please review the highlighted fields and try again.</div>
            @endif
            <form class="agent-login-form" method="POST" action="{{ route('agent.register.store') }}">
                @csrf
                <div class="agent-field"><label for="name">Full name <span class="agent-required">*</span></label><input class="agent-input @error('name') agent-input-error @enderror" id="name" name="name" value="{{ old('name') }}" maxlength="255" autocomplete="name" required>@error('name')<span class="agent-error">{{ $message }}</span>@enderror</div>
                <div class="agent-field"><label for="email">Email address <span class="agent-required">*</span></label><input class="agent-input @error('email') agent-input-error @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>@error('email')<span class="agent-error">{{ $message }}</span>@enderror</div>
                <div class="agent-field"><label for="mobile">Mobile number <span class="agent-required">*</span></label><input class="agent-input @error('mobile') agent-input-error @enderror" id="mobile" name="mobile" value="{{ old('mobile') }}" maxlength="30" autocomplete="tel" placeholder="09XX XXX XXXX" required>@error('mobile')<span class="agent-error">{{ $message }}</span>@enderror</div>
                <div class="agent-form-grid agent-registration-passwords"><div class="agent-field"><label for="password">Password <span class="agent-required">*</span></label><input class="agent-input @error('password') agent-input-error @enderror" id="password" name="password" type="password" minlength="8" autocomplete="new-password" required></div><div class="agent-field"><label for="password_confirmation">Confirm password <span class="agent-required">*</span></label><input class="agent-input" id="password_confirmation" name="password_confirmation" type="password" minlength="8" autocomplete="new-password" required></div></div>
                @error('password')<span class="agent-error">{{ $message }}</span>@enderror
                <label class="agent-checkbox agent-terms"><input name="terms" type="checkbox" value="1" required><span>I understand that registration requires approval, earnings are based only on qualifying successful orders, and payout is coordinated by Pahatud operations.</span></label>
                @error('terms')<span class="agent-error">{{ $message }}</span>@enderror
                <button class="agent-button agent-button-primary agent-login-submit" type="submit">Submit agent application</button>
                <p class="agent-application-login">Already approved? <a class="agent-text-link" href="{{ route('agent.login') }}">Sign in to the Agent Dashboard</a></p>
            </form>
        </div>
    </section>
</main>

<footer class="agent-program-footer"><a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>Pahatud</strong><span>Agent Program</span></span></a><p>Helping local restaurants connect, deliver, and grow.</p><span>© {{ date('Y') }} Pahatud. All rights reserved.</span></footer>
</body>
</html>
