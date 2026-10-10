<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/favicon.ico') }}" type="image/ico">
    <title>Become a PahatudFood Merchant</title>
    <meta name="description" content="Grow your restaurant with PahatudFood. Offer dine-in, pickup, and delivery and manage every order in one merchant dashboard.">
    @include('agent.partials.styles')
</head>
<body class="agent-program-body merchant-program-body">
<header class="agent-program-nav">
    <a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>PahatudFood</strong><span>Merchant Partners</span></span></a>
    <nav aria-label="Merchant program navigation"><a href="#how-it-works">How it works</a><a href="#services">Services</a><a href="#requirements">Requirements</a><a href="#commission">Commission</a><a class="agent-button agent-button-secondary" href="{{ route('merchant.login') }}">Merchant login</a><a class="agent-button agent-button-primary" href="#apply">Become a Merchant</a></nav>
</header>

<main>
    <section class="agent-program-hero">
        <div class="agent-program-hero-copy">
            <p class="agent-eyebrow">Serve more. Sell smarter. Grow locally.</p>
            <h1>Turn more hungry customers into regulars.</h1>
            <p class="agent-program-lead">Bring your restaurant or food business to PahatudFood and manage dine-in, pickup, and delivery from one merchant experience built for local operators.</p>
            <div class="agent-program-actions"><a class="agent-button agent-button-primary" href="#apply">Become a Merchant</a><a class="agent-button agent-button-secondary" href="#how-it-works">See how it works</a></div>
            <div class="agent-program-trust"><span>No application fee</span><span>Local customer reach</span><span>Flexible service options</span></div>
        </div>
        <aside class="agent-earning-preview" aria-label="PahatudFood commission overview">
            <span class="agent-earning-label">Standard marketplace commission</span>
            <strong>{{ number_format($commissionPercentage, 0) }}%</strong>
            <p>of the eligible food order subtotal. Delivery charges are kept separate from the merchant commission calculation.</p>
            <div class="agent-earning-equation"><span>₱1,000 eligible food subtotal</span><b>PahatudFood commission: ₱{{ number_format(1000 * ($commissionPercentage / 100), 2) }}</b><strong>Your proceeds: ₱{{ number_format(1000 * (1 - ($commissionPercentage / 100)), 2) }}</strong></div>
            <small>Illustrative calculation before merchant taxes, discounts, refunds, or other agreed adjustments. Your final commercial terms are confirmed during onboarding.</small>
        </aside>
    </section>

    <section class="agent-program-section agent-benefit-section">
        <div class="agent-program-heading"><p class="agent-eyebrow">Built for food businesses</p><h2>More ways to reach customers</h2><p>PahatudFood gives your team a clear path from menu setup to order fulfillment and reporting.</p></div>
        <div class="agent-benefit-grid">
            <article><span>01</span><h3>Discoverability</h3><p>Put your menu in front of nearby customers already looking for places to eat and order from.</p></article>
            <article><span>02</span><h3>One order workspace</h3><p>Receive and manage supported orders from the merchant dashboard with clear status updates.</p></article>
            <article><span>03</span><h3>Flexible fulfillment</h3><p>Choose the services that fit your operation today and expand from dine-in to pickup or delivery.</p></article>
            <article><span>04</span><h3>Promotions and insights</h3><p>Create offers, review sales activity, and use reports to understand how your business performs.</p></article>
        </div>
    </section>

    <section class="agent-program-section agent-program-steps" id="how-it-works">
        <div class="agent-program-heading"><p class="agent-eyebrow">A straightforward partnership</p><h2>How PahatudFood works</h2></div>
        <div class="agent-step-grid">
            <article><span>1</span><div><h3>Send your application</h3><p>Share your contact, business, location, and preferred service details.</p></div></article>
            <article><span>2</span><div><h3>Complete verification</h3><p>After initial approval, our team will request and verify the required business documents.</p></div></article>
            <article><span>3</span><div><h3>Build your storefront</h3><p>Add your locations, operating hours, menu items, prices, and available fulfillment options.</p></div></article>
            <article><span>4</span><div><h3>Receive orders</h3><p>Use the merchant dashboard to confirm, prepare, and complete customer orders.</p></div></article>
            <article><span>5</span><div><h3>Review and grow</h3><p>Track sales and statements, run promotions, and refine your offering as demand grows.</p></div></article>
        </div>
    </section>

    <section class="agent-program-section merchant-services-section" id="services">
        <div class="agent-program-heading"><p class="agent-eyebrow">Choose what fits your operation</p><h2>Serve customers their way</h2><p>You can request one or more service types in your application. Final availability depends on your location and operational readiness.</p></div>
        <div class="merchant-service-grid">
            <article><span aria-hidden="true">⌂</span><div><h3>Dine-in</h3><p>Help customers discover your restaurant and support on-premise dining and table-oriented experiences.</p></div></article>
            <article><span aria-hidden="true">◫</span><div><h3>Pickup</h3><p>Let customers order ahead, then collect prepared food directly from your selected location.</p></div></article>
            <article><span aria-hidden="true">→</span><div><h3>Delivery</h3><p>Reach customers beyond your storefront with delivery availability coordinated through Pahatud.</p></div></article>
        </div>
    </section>

    <section class="agent-program-section merchant-requirements-section" id="requirements">
        <div class="merchant-requirements-layout">
            <div class="agent-program-heading"><p class="agent-eyebrow">Prepare for verification</p><h2>What you will need</h2><p>The application below is the first step. If it is approved, the onboarding team will guide you through document upload and account activation.</p></div>
            <div class="merchant-requirement-list">
                <div><span>01</span><p><strong>Business registration</strong>DTI, SEC, CDA, or the registration that applies to your business structure.</p></div>
                <div><span>02</span><p><strong>Tax information</strong>BIR registration and TIN details matching the registered business.</p></div>
                <div><span>03</span><p><strong>Operating permits</strong>Current mayor's/business permit and applicable food or sanitary permits.</p></div>
                <div><span>04</span><p><strong>Authorized contact</strong>A valid government ID and authorization if the applicant is not the owner.</p></div>
                <div><span>05</span><p><strong>Payout details</strong>A bank or supported payout account in the business or authorized owner's name.</p></div>
                <div><span>06</span><p><strong>Store information</strong>Menu, prices, operating hours, branch addresses, and clear food or storefront photos.</p></div>
            </div>
        </div>
    </section>

    <section class="agent-program-section agent-commission-section" id="commission">
        <div class="agent-program-heading"><p class="agent-eyebrow">Simple, visible pricing</p><h2>Know how the standard commission works</h2><p>The current standard rate is {{ number_format($commissionPercentage, 2) }}% of the eligible food order subtotal. PahatudFood uses this fee to support the marketplace, order technology, customer access, and merchant operations.</p></div>
        <div class="agent-commission-layout">
            <div class="agent-formula-card"><span>Standard formula</span><strong>Eligible food subtotal × {{ number_format($commissionPercentage, 2) }}% = PahatudFood commission</strong><p>Example: ₱1,000 × {{ number_format($commissionPercentage, 0) }}% = ₱{{ number_format(1000 * ($commissionPercentage / 100), 2) }} commission. Delivery fees are not part of this example.</p></div>
            <div class="merchant-commission-notes">
                <div><strong>Delivery charges</strong><p>Delivery fees are separate from the eligible food subtotal used in the standard commission example.</p></div>
                <div><strong>Discounts and refunds</strong><p>Promotions, refunds, cancellations, and other adjustments may change the final settlement amount.</p></div>
                <div><strong>Final commercial terms</strong><p>Your approved rate, payout schedule, and service coverage are confirmed before activation.</p></div>
            </div>
        </div>
        <p class="agent-program-disclaimer">The displayed rate is the platform's current standard merchant commission and is saved with your application for review. Any different negotiated terms must be documented during onboarding.</p>
    </section>

    <section class="agent-program-section agent-faq-section">
        <div class="agent-program-heading"><p class="agent-eyebrow">Questions, answered</p><h2>Merchant FAQs</h2></div>
        <div class="agent-faq-list">
            <details open><summary>Does applying create a live merchant account?</summary><p>No. This form creates an application for review. Approved applicants receive next-step instructions for document verification, account setup, menu preparation, and activation.</p></details>
            <details><summary>Can I offer only pickup or dine-in?</summary><p>Yes. Select the services that fit your business. PahatudFood will confirm which options can be activated for each location during onboarding.</p></details>
            <details><summary>When will my restaurant appear to customers?</summary><p>Your storefront goes live only after the application, required documents, location, menu, and operating setup have been reviewed and activated.</p></details>
            <details><summary>How will I know the decision?</summary><p>You will receive a confirmation after submission and another email after an administrator approves or declines your application.</p></details>
        </div>
    </section>

    <section class="agent-program-apply" id="apply">
        <div class="agent-apply-copy"><p class="agent-eyebrow">Start your application</p><h2>Become a PahatudFood merchant</h2><p>Tell us about your food business. This initial form takes only a few minutes; documents are requested after the first review.</p><ol><li>Submit your business details</li><li>Receive an email confirmation</li><li>Wait for the review decision</li><li>Complete onboarding and verification</li></ol></div>
        <div class="agent-application-card">
            @if ($errors->any())
                <div class="agent-alert agent-alert-error" role="alert">Please review the highlighted fields and try again.</div>
            @endif
            <form class="agent-login-form" method="POST" action="{{ route('merchant.register.submit') }}">
                @csrf
                <div class="agent-form-grid">
                    <div class="agent-field"><label for="business_name">Store or restaurant name <span class="agent-required">*</span></label><input class="agent-input @error('business_name') agent-input-error @enderror" id="business_name" name="business_name" value="{{ old('business_name') }}" maxlength="255" required>@error('business_name')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="registered_business_name">Registered business name <span class="agent-required">*</span></label><input class="agent-input @error('registered_business_name') agent-input-error @enderror" id="registered_business_name" name="registered_business_name" value="{{ old('registered_business_name') }}" maxlength="255" required>@error('registered_business_name')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="owner_name">Owner / authorized representative <span class="agent-required">*</span></label><input class="agent-input @error('owner_name') agent-input-error @enderror" id="owner_name" name="owner_name" value="{{ old('owner_name') }}" maxlength="255" autocomplete="name" required>@error('owner_name')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="business_structure">Business structure <span class="agent-required">*</span></label><select class="agent-input @error('business_structure') agent-input-error @enderror" id="business_structure" name="business_structure" required><option value="">Select structure</option>@foreach (['sole_proprietorship' => 'Sole proprietorship', 'corporation' => 'Corporation', 'partnership' => 'Partnership', 'cooperative' => 'Cooperative'] as $value => $label)<option value="{{ $value }}" @selected(old('business_structure') === $value)>{{ $label }}</option>@endforeach</select>@error('business_structure')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="email">Business email <span class="agent-required">*</span></label><input class="agent-input @error('email') agent-input-error @enderror" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="255" autocomplete="email" required>@error('email')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="mobile">Mobile number <span class="agent-required">*</span></label><input class="agent-input @error('mobile') agent-input-error @enderror" id="mobile" name="mobile" value="{{ old('mobile') }}" maxlength="30" autocomplete="tel" placeholder="09XX XXX XXXX" required>@error('mobile')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="telephone">Telephone <small>(optional)</small></label><input class="agent-input @error('telephone') agent-input-error @enderror" id="telephone" name="telephone" value="{{ old('telephone') }}" maxlength="30">@error('telephone')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="cuisine">Cuisine / food category <small>(optional)</small></label><input class="agent-input @error('cuisine') agent-input-error @enderror" id="cuisine" name="cuisine" value="{{ old('cuisine') }}" maxlength="120" placeholder="e.g. Filipino, bakery, coffee">@error('cuisine')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field agent-field-full"><label for="address">Primary business address <span class="agent-required">*</span></label><input class="agent-input @error('address') agent-input-error @enderror" id="address" name="address" value="{{ old('address') }}" maxlength="500" autocomplete="street-address" required>@error('address')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="city">City / municipality <span class="agent-required">*</span></label><input class="agent-input @error('city') agent-input-error @enderror" id="city" name="city" value="{{ old('city') }}" maxlength="120" required>@error('city')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field"><label for="branch_count">Number of branches <span class="agent-required">*</span></label><input class="agent-input @error('branch_count') agent-input-error @enderror" id="branch_count" name="branch_count" type="number" value="{{ old('branch_count', 1) }}" min="1" max="500" required>@error('branch_count')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field agent-field-full"><label>Services you want to offer <span class="agent-required">*</span></label><div class="merchant-service-options">@foreach ($services as $value => $label)<label><input name="services[]" type="checkbox" value="{{ $value }}" @checked(in_array($value, old('services', []), true))><span><strong>{{ $label }}</strong><small>{{ ['dine_in' => 'Welcome guests at your location', 'pickup' => 'Prepare orders for collection', 'delivery' => 'Send orders to customers nearby'][$value] }}</small></span></label>@endforeach</div>@error('services')<span class="agent-error">{{ $message }}</span>@enderror @error('services.*')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field agent-field-full"><label for="website">Website or social page <small>(optional)</small></label><input class="agent-input @error('website') agent-input-error @enderror" id="website" name="website" type="url" value="{{ old('website') }}" maxlength="255" placeholder="https://">@error('website')<span class="agent-error">{{ $message }}</span>@enderror</div>
                    <div class="agent-field agent-field-full"><label for="business_description">Tell us about your business <small>(optional)</small></label><textarea class="agent-input @error('business_description') agent-input-error @enderror" id="business_description" name="business_description" maxlength="2000" placeholder="What do you serve, and what makes your business special?">{{ old('business_description') }}</textarea>@error('business_description')<span class="agent-error">{{ $message }}</span>@enderror</div>
                </div>
                <label class="agent-checkbox agent-terms"><input name="terms" type="checkbox" value="1" @checked(old('terms')) required><span>I confirm that these details are accurate, I am authorized to apply for this business, and I understand that submission does not guarantee approval or immediate activation.</span></label>
                @error('terms')<span class="agent-error">{{ $message }}</span>@enderror
                @include('components.turnstile', ['action' => 'merchant_application'])
                <button class="agent-button agent-button-primary agent-login-submit" type="submit">Submit merchant application</button>
                <p class="agent-application-login">Already a merchant? <a class="agent-text-link" href="{{ route('merchant.login') }}">Sign in to the Merchant Dashboard</a></p>
            </form>
        </div>
    </section>
</main>

<footer class="agent-program-footer"><a class="agent-brand" href="{{ route('home') }}"><img src="{{ asset('images/logo.jpg') }}" alt="Pahatud"><span class="agent-brand-copy"><strong>PahatudFood</strong><span>Merchant Partners</span></span></a><p>Helping local food businesses connect, serve, and grow.</p><span>© {{ date('Y') }} Pahatud. All rights reserved.</span></footer>
</body>
</html>
