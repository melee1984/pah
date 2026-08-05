<!DOCTYPE html>
<html lang="en">
<head>
    @include('includes.meta')
    @hasSection('title')
        <title>@yield('title') | Pahatud Delivery Services</title>
    @else
        <title>Pahatud Delivery Services</title>
    @endif
    @include('includes.gtag')
    @vite(['resources/js/app.js'])
    <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
</head>
<body class="landing-page">
    <div id="app">
        <header class="landing-header">
            <div class="container landing-header-inner">
                <a class="landing-logo" href="{{ route('home') }}" aria-label="PahatudFood — Online Food Ordering and Delivery Services" title="PahatudFood — Online Food Ordering &amp; Delivery Services">
                    <img src="{{ asset('images/logo.jpg') }}" alt="PahatudFood app logo">
                </a>

                <nav class="landing-nav" aria-label="Homepage navigation">
                    <a href="#mobile-app">Mobile app</a>
                    <a href="#restaurant-partners">Our partners</a>
                    <a href="#how-it-works">How it works</a>
                    <a class="landing-nav-button" href="#become-a-partner">Register as a partner</a>
                </nav>

                <details class="landing-mobile-nav">
                    <summary aria-label="Open navigation"><span></span><span></span><span></span></summary>
                    <div>
                        <a href="#mobile-app">Mobile app</a>
                        <a href="#restaurant-partners">Our partners</a>
                        <a href="#how-it-works">How it works</a>
                        <a href="#become-a-partner">Register as a partner</a>
                    </div>
                </details>
            </div>
        </header>

        @yield('content')
    </div>

    @include('includes.footer')
    <a href="#" class="scrollToTop"><i class="icofont-swoosh-up"></i></a>
    @include('pages.includes.js')
    <script>
        document.addEventListener('click', function (event) {
            const playStoreLink = event.target.closest('a[href*="play.google.com/store/apps/details?id=io.pahatud.com"]');

            if (!playStoreLink) {
                return;
            }

            event.preventDefault();
            window.alert('The PahatudFood mobile app is currently awaiting Google Play approval. Please check back soon!');
        });
    </script>
</body>
</html>
