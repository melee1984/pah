@extends('templates.landing')

@section('title', 'Food delivery made local')

@section('content')
<main class="home-landing">
    <section class="home-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <span class="home-eyebrow">Davao's local delivery marketplace</span>
                    <h1 class="home-rotating-title" aria-label="Your local favorites delivered. Online food ordering at your fingertips.">
                        <span class="home-title-frame home-title-frame-first" aria-hidden="true">
                            Your local favorites,<br><span class="home-title-accent">delivered.</span>
                        </span>
                        <span class="home-title-frame home-title-frame-second" aria-hidden="true">
                            Online food ordering<br><span class="home-title-accent">at your fingertips.</span>
                        </span>
                    </h1>
                    <p class="home-hero-copy"><strong>PahatudFood</strong> brings the food you love straight to your door. Download the mobile app and order in just a few taps.</p>

                    <div class="home-hero-actions">
                        <a class="home-button home-button-primary" href="https://play.google.com/store/apps/details?id=io.pahatud.com" target="_blank" rel="noopener noreferrer">Download the app</a>
                        <a class="home-text-link" href="#how-it-works">See how it works <span aria-hidden="true">&rarr;</span></a>
                    </div>
                </div>

                <div class="col-lg-6 home-hero-visual">
                    <div class="home-hero-orbit home-hero-orbit-one"></div>
                    <div class="home-hero-orbit home-hero-orbit-two"></div>
                    <div class="home-phone-showcase home-phone-showcase-hero" aria-label="PahatudFood mobile app preview">
                        <figure class="home-phone-screen home-phone-screen-secondary">
                            <img src="{{ asset('images/app-preview/restaurants.png') }}" alt="PahatudFood restaurants near you screen" width="720" height="1600" loading="eager">
                        </figure>
                        <figure class="home-phone-screen home-phone-screen-primary">
                            <img src="{{ asset('images/app-preview/home.png') }}" alt="PahatudFood mobile app home screen" width="720" height="1600" loading="eager" fetchpriority="high">
                        </figure>
                    </div>
                    <div class="home-floating-note home-floating-note-top">
                        <span class="material-icons" aria-hidden="true">restaurant</span>
                        <strong>Local choices</strong>
                    </div>
                    <div class="home-floating-note home-floating-note-bottom">
                        <span class="material-icons" aria-hidden="true">delivery_dining</span>
                        <strong>Delivered to you</strong>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-app-section" id="mobile-app" aria-labelledby="app-heading">
        <div class="container">
            <div class="home-app-card">
                <div class="row align-items-center">
                    <div class="col-lg-6 home-app-image">
                        <div class="home-phone-showcase home-phone-showcase-feature" aria-label="PahatudFood login and order tracking preview">
                            <figure class="home-phone-screen home-phone-screen-login">
                                <img src="{{ asset('images/app-preview/login.png') }}" alt="PahatudFood mobile app login screen" loading="lazy" width="720" height="1600">
                            </figure>
                            <figure class="home-phone-screen home-phone-screen-tracking">
                                <img src="{{ asset('images/app-preview/order-tracking.png') }}" alt="PahatudFood live order tracking screen" loading="lazy" width="720" height="1600">
                            </figure>
                        </div>
                    </div>
                    <div class="col-lg-6 home-app-content">
                        <span class="home-eyebrow home-eyebrow-light">Pahatud in your pocket</span>
                        <h2 id="app-heading">Order anytime,<br>wherever you are.</h2>
                        <p>Browse local menus, place your order, and follow your delivery from one easy mobile app.</p>
                        <ul class="home-check-list">
                            <li><span class="material-icons" aria-hidden="true">check_circle</span> Discover nearby restaurants</li>
                            <li><span class="material-icons" aria-hidden="true">check_circle</span> Simple mobile ordering</li>
                            <li><span class="material-icons" aria-hidden="true">check_circle</span> Convenient cash on delivery</li>
                        </ul>
                        <a class="home-play-link" href="https://play.google.com/store/apps/details?id=io.pahatud.com" target="_blank" rel="noopener noreferrer" aria-label="Get Pahatud on Google Play">
                            <img src="{{ asset('images/google-play.png') }}" alt="Get it on Google Play" width="200">
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-partners" id="restaurant-partners" aria-labelledby="partners-heading">
        <div class="container">
            <div class="home-section-heading">
                <div>
                    <span class="home-eyebrow">Made better together</span>
                    <h2 id="partners-heading">Meet our restaurant partners.</h2>
                    <p>From neighborhood favorites to new discoveries, these local businesses are available in the Pahatud app.</p>
                </div>
                <a class="home-text-link" href="https://play.google.com/store/apps/details?id=io.pahatud.com" target="_blank" rel="noopener noreferrer">Find them in the app <span aria-hidden="true">&rarr;</span></a>
            </div>

            <div class="home-partner-grid">
                @forelse($partners as $partner)
                    <article class="home-partner-card">
                        <div class="home-partner-image">
                            <img src="{{ $partner->img }}" alt="{{ $partner->restaurant_name }}" loading="lazy">
                        </div>
                        <div class="home-partner-name">
                            <span>{{ $partner->restaurant_name }}</span>
                            <span class="material-icons" aria-hidden="true">smartphone</span>
                        </div>
                    </article>
                @empty
                    <div class="home-partner-empty">Our local restaurant partners will appear here soon.</div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="home-how" id="how-it-works" aria-labelledby="how-heading">
        <div class="container">
            <div class="home-section-heading home-section-heading-centered">
                <div>
                    <span class="home-eyebrow">Easy from start to finish</span>
                    <h2 id="how-heading">How it works.</h2>
                    <p>Your next meal is only a few simple steps away.</p>
                </div>
            </div>

            <div class="home-step-grid">
                <article class="home-step-card">
                    <span class="home-step-number">01</span>
                    <span class="home-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 9h16v11H4z" />
                            <path d="M3 9l2-5h14l2 5" />
                            <path d="M3 9c0 1.7 2.8 2.5 4.5.8 1.7 1.7 4.3 1.7 6 0 1.7 1.7 4.5.9 4.5-.8" />
                            <path d="M9 20v-6h6v6" />
                        </svg>
                    </span>
                    <h3>Choose a restaurant</h3>
                    <p>Open the Pahatud app and explore trusted local partners.</p>
                </article>
                <article class="home-step-card">
                    <span class="home-step-number">02</span>
                    <span class="home-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 8h14l1 12H4L5 8z" />
                            <path d="M9 9V6a3 3 0 0 1 6 0v3" />
                            <path d="M9 13h.01M15 13h.01" />
                        </svg>
                    </span>
                    <h3>Place your order</h3>
                    <p>Pick your favorites and place the order directly in the mobile app.</p>
                </article>
                <article class="home-step-card">
                    <span class="home-step-number">03</span>
                    <span class="home-step-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h11v10H3z" />
                            <path d="M14 9h4l3 4v3h-7z" />
                            <path d="M17 9v4h4" />
                            <circle cx="7" cy="18" r="2" />
                            <circle cx="18" cy="18" r="2" />
                        </svg>
                    </span>
                    <h3>We deliver</h3>
                    <p>Sit back while a Pahatud rider brings your order to your door.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="home-partner-signup" id="become-a-partner" aria-labelledby="partner-signup-heading">
        <div class="container">
            <div class="home-signup-shell">
                <div class="row align-items-center">
                    <div class="col-lg-5 home-signup-copy">
                        <span class="home-eyebrow home-eyebrow-light">Grow with Pahatud</span>
                        <h2 id="partner-signup-heading">Bring your restaurant to more customers.</h2>
                        <p>Join our growing local marketplace and make delivery easier for your business and your customers.</p>
                        <ul class="home-partner-benefits">
                            <li><span class="material-icons" aria-hidden="true">percent</span><div><strong>Straightforward 15% commission</strong><small>A merchant-friendly rate that is confirmed with you before activation.</small></div></li>
                            <li><span class="material-icons" aria-hidden="true">trending_up</span><div><strong>Reach more customers</strong><small>Be discovered by diners across your service area.</small></div></li>
                            <li><span class="material-icons" aria-hidden="true">handshake</span><div><strong>Local partnership</strong><small>Work with a delivery platform that understands your community.</small></div></li>
                            <li><span class="material-icons" aria-hidden="true">support_agent</span><div><strong>Support when you need it</strong><small>Our team is here to help you get started.</small></div></li>
                        </ul>
                    </div>
                    <div class="col-lg-6 offset-lg-1">
                        <div class="home-signup-form">
                            <span class="home-form-kicker">Partner application</span>
                            <h3>Tell us about your restaurant</h3>
                            <p>Complete the form and our team will get in touch.</p>
                            <partner-form :accounttype='@json($accountTypes)'></partner-form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>
@endsection
