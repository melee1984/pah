<section class="public-page-hero {{ ($variant ?? null) === 'contact' ? 'public-contact-hero' : '' }}">
    <div class="container public-page-hero-inner">
        <div class="public-page-hero-copy">
            <nav class="public-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <i class="icofont-rounded-right" aria-hidden="true"></i>
                <span aria-current="page">{{ $pageName ?? $title }}</span>
            </nav>
            <span class="public-page-kicker">{{ $kicker ?? 'Pahatud information' }}</span>
            <h1>{{ $title }}</h1>
            @isset($description)
                <p>{{ $description }}</p>
            @endisset

            @if (($variant ?? null) === 'contact')
                <div class="public-hero-actions">
                    <a href="#contact-form" class="public-hero-primary">Send us a message <i class="icofont-arrow-right" aria-hidden="true"></i></a>
                    <a href="tel:+639162986547" class="public-hero-secondary"><i class="icofont-phone" aria-hidden="true"></i> +63 916 298 6547</a>
                </div>
            @endif
        </div>

        @if (($variant ?? null) === 'contact')
            <aside class="public-support-card" aria-label="Customer support information">
                <div class="public-support-card-top">
                    <span class="public-support-icon"><i class="icofont-support" aria-hidden="true"></i></span>
                    <span class="public-support-status"><i aria-hidden="true"></i> Local support</span>
                </div>
                <h2>Real people.<br>Ready to help.</h2>
                <p>Tell us what happened and we’ll connect you with the right person.</p>
                <div class="public-support-details">
                    <div><i class="icofont-clock-time" aria-hidden="true"></i><span><strong>Office hours</strong>Mon–Sat, 8 AM–6 PM</span></div>
                    <div><i class="icofont-location-pin" aria-hidden="true"></i><span><strong>Based in</strong>Davao City, Philippines</span></div>
                </div>
                <div class="public-support-route" aria-hidden="true"><span></span><i class="icofont-fast-delivery"></i></div>
            </aside>
        @endif
    </div>
</section>
