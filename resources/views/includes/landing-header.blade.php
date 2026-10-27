<header class="landing-header">
    <div class="container landing-header-inner">
        <a class="landing-logo" href="{{ route('home') }}" aria-label="PahatudFood — Online Food Ordering and Delivery Services" title="PahatudFood — Online Food Ordering &amp; Delivery Services">
            <img src="{{ asset('images/logo.jpg') }}" alt="PahatudFood logo">
            <span><strong>PahatudFood</strong><small>Local food delivery</small></span>
        </a>

        <div class="landing-header-actions">
            <nav class="landing-nav" aria-label="Homepage navigation">
                <a href="{{ route('home') }}#mobile-app">Mobile app</a>
                <a href="{{ route('home') }}#restaurant-partners">Our partners</a>
                <a href="{{ route('home') }}#how-it-works">How it works</a>
                <a class="landing-nav-button" href="{{ route('home') }}#become-a-partner">Register as a partner</a>
            </nav>

            @include('includes.customer-account-menu')
            <details class="landing-mobile-nav">
                <summary aria-label="Open navigation"><span></span><span></span><span></span></summary>
                <div>
                    <a href="{{ route('home') }}#mobile-app">Mobile app</a>
                    <a href="{{ route('home') }}#restaurant-partners">Our partners</a>
                    <a href="{{ route('home') }}#how-it-works">How it works</a>
                    <a href="{{ route('home') }}#become-a-partner">Register as a partner</a>
                </div>
            </details>
        </div>
    </div>
</header>
