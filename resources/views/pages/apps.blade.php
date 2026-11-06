@extends('templates.landing')

@section('title', 'Download the Pahatud Android apps')
@section('description', 'Download the official PahatudFood, Pahatud Merchant, and Pahatud Rider Android apps from Google Play, with a scannable QR code for each app.')
@section('canonical', route('apps.index'))

@section('content')
<main class="app-download-page">
    <section class="app-download-hero" aria-labelledby="app-download-title">
        <div class="container">
            <nav class="app-download-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <span aria-hidden="true">/</span>
                <span aria-current="page">Mobile apps</span>
            </nav>

            <div class="app-download-hero-grid">
                <div class="app-download-hero-copy">
                    <span class="app-download-eyebrow">Pahatud mobile apps</span>
                    <h1 id="app-download-title">Your local favorites,<br><span>right in your pocket.</span></h1>
                    <p>Order local favorites, manage your business, or deliver across the community with a Pahatud Android app made for you.</p>
                    <a class="app-download-platform" data-direct-download href="https://play.google.com/store/apps/details?id=io.pahatud.user" target="_blank" rel="noopener noreferrer" aria-label="Download PahatudFood for Android from Google Play">
                        <span class="material-icons" aria-hidden="true">android</span>
                        <span><strong>Download for Android</strong><small>Open PahatudFood on Google Play</small></span>
                        <span class="material-icons app-download-platform-arrow" aria-hidden="true">arrow_outward</span>
                    </a>
                </div>

                <div class="app-download-hero-visual" aria-label="PahatudFood mobile app preview">
                    <span class="app-download-orbit app-download-orbit-one" aria-hidden="true"></span>
                    <span class="app-download-orbit app-download-orbit-two" aria-hidden="true"></span>
                    <figure class="app-download-phone app-download-phone-back">
                        <img src="{{ asset('images/app-preview/order-tracking.png') }}" alt="Tracking a PahatudFood delivery in the Android app" width="720" height="1600">
                    </figure>
                    <figure class="app-download-phone app-download-phone-front">
                        <img src="{{ asset('images/app-preview/home.png') }}" alt="PahatudFood Android app home screen" width="720" height="1600" fetchpriority="high">
                    </figure>
                    <span class="app-download-floating-note app-download-note-top"><i class="icofont-fast-delivery" aria-hidden="true"></i> Easy delivery</span>
                    <span class="app-download-floating-note app-download-note-bottom"><i class="icofont-location-pin" aria-hidden="true"></i> Local choices</span>
                </div>
            </div>
        </div>
    </section>

    <section class="app-download-collection" aria-labelledby="app-collection-title">
        <div class="container">
            <header class="app-download-section-heading">
                <div>
                    <span class="app-download-eyebrow">One platform, every side of delivery</span>
                    <h2 id="app-collection-title">Choose the app made for you.</h2>
                </div>
                <p>Customers, local businesses, and delivery partners can each download the Android experience made for their day.</p>
            </header>

            <article class="app-download-feature-card" id="pahatud-food">
                <div class="app-download-feature-copy">
                    <div class="app-download-app-heading">
                        <img class="app-download-app-icon" src="{{ asset('images/logo.jpg') }}" alt="PahatudFood app icon" width="112" height="112">
                        <div>
                            <span class="app-download-status app-download-status-live"><i class="icofont-check-circled" aria-hidden="true"></i> Available now</span>
                            <h3>PahatudFood</h3>
                            <p>Food &amp; local delivery</p>
                        </div>
                    </div>

                    <p class="app-download-feature-description">Discover local restaurants, explore their menus, place your order, and follow your delivery—all from one simple Android app.</p>

                    <ul class="app-download-benefits" aria-label="PahatudFood features">
                        <li><span class="material-icons" aria-hidden="true">storefront</span> Browse trusted local restaurants</li>
                        <li><span class="material-icons" aria-hidden="true">receipt_long</span> Order in just a few taps</li>
                        <li><span class="material-icons" aria-hidden="true">delivery_dining</span> Follow your delivery progress</li>
                    </ul>

                    <a class="app-download-play-button" data-direct-download href="https://play.google.com/store/apps/details?id=io.pahatud.user" target="_blank" rel="noopener noreferrer" aria-label="Download PahatudFood for Android from Google Play">
                        <img src="{{ asset('images/google-play.png') }}" alt="Get PahatudFood on Google Play" width="220" height="85">
                    </a>
                    <p class="app-download-requirement"><span class="material-icons" aria-hidden="true">android</span> Android app &middot; Google Play</p>
                </div>

                <aside class="app-download-qr-card" aria-label="QR code for the PahatudFood Google Play listing">
                    <span class="app-download-qr-kicker">On a computer?</span>
                    <h3>Scan to download</h3>
                    <div class="app-download-qr-wrap">
                        <img src="{{ asset('images/app-download/pahatud-food-qr.svg') }}" alt="QR code linking to PahatudFood on Google Play" width="240" height="240">
                    </div>
                    <p>Open your phone camera and point it at the code.</p>
                </aside>
            </article>

            <div class="app-download-secondary-grid" aria-label="Pahatud Merchant and Rider Android apps">
                <article class="app-download-secondary-card">
                    <div class="app-download-secondary-main">
                        <div class="app-download-secondary-heading">
                            <img src="{{ asset('images/app-download/pahatud-merchant-icon.png') }}" alt="Pahatud Merchant app icon" width="80" height="80">
                            <div>
                                <span class="app-download-status app-download-status-live"><i class="icofont-check-circled" aria-hidden="true"></i> Available now</span>
                                <h3>Pahatud Merchant</h3>
                            </div>
                        </div>
                        <p>Manage orders, menus, and day-to-day restaurant operations from one merchant experience.</p>
                        <a class="app-download-play-button app-download-play-button-compact" data-direct-download href="https://play.google.com/store/apps/details?id=io.pahatud.merchant" target="_blank" rel="noopener noreferrer" aria-label="Download Pahatud Merchant for Android from Google Play">
                            <img src="{{ asset('images/google-play.png') }}" alt="Get Pahatud Merchant on Google Play" width="220" height="85">
                        </a>
                    </div>
                    <div class="app-download-secondary-qr">
                        <img src="{{ asset('images/app-download/pahatud-merchant-qr.svg') }}" alt="QR code linking to Pahatud Merchant on Google Play" width="150" height="150">
                        <span>Scan to download</span>
                    </div>
                </article>

                <article class="app-download-secondary-card">
                    <div class="app-download-secondary-main">
                        <div class="app-download-secondary-heading">
                            <img src="{{ asset('images/app-download/pahatud-rider-icon.png') }}" alt="Pahatud Rider app icon" width="80" height="80">
                            <div>
                                <span class="app-download-status app-download-status-live"><i class="icofont-check-circled" aria-hidden="true"></i> Available now</span>
                                <h3>Pahatud Rider</h3>
                            </div>
                        </div>
                        <p>Accept delivery requests, coordinate pickups, and stay on top of active trips while on the road.</p>
                        <a class="app-download-play-button app-download-play-button-compact" data-direct-download href="https://play.google.com/store/apps/details?id=io.pahatud.rider.user" target="_blank" rel="noopener noreferrer" aria-label="Download Pahatud Rider for Android from Google Play">
                            <img src="{{ asset('images/google-play.png') }}" alt="Get Pahatud Rider on Google Play" width="220" height="85">
                        </a>
                    </div>
                    <div class="app-download-secondary-qr">
                        <img src="{{ asset('images/app-download/pahatud-rider-qr.svg') }}" alt="QR code linking to Pahatud Rider on Google Play" width="150" height="150">
                        <span>Scan to download</span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <section class="app-download-help" aria-labelledby="app-help-title">
        <div class="container">
            <div class="app-download-help-shell">
                <div>
                    <span class="app-download-eyebrow">From scan to order</span>
                    <h2 id="app-help-title">Ready in three easy steps.</h2>
                </div>
                <ol>
                    <li><span>01</span><div><strong>Scan or open</strong><small>Use the QR code or Google Play button.</small></div></li>
                    <li><span>02</span><div><strong>Install</strong><small>Download PahatudFood securely from Google Play.</small></div></li>
                    <li><span>03</span><div><strong>Start ordering</strong><small>Explore nearby food and local favorites.</small></div></li>
                </ol>
            </div>
        </div>
    </section>
</main>
@endsection
