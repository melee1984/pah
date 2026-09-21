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
                    <img src="{{ asset('images/logo.jpg') }}" alt="PahatudFood logo">
                    <span><strong>PahatudFood</strong><small>Local food delivery</small></span>
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

    <div class="home-download-dialog" id="homeDownloadDialog" role="dialog" aria-modal="true" aria-labelledby="homeDownloadTitle" aria-describedby="homeDownloadDescription" aria-hidden="true" data-preference-key="pahatud_app_download_prompt_{{ Auth::check() ? Auth::id() : 'guest' }}">
        <button class="home-download-backdrop" type="button" data-download-block aria-label="Close and block this prompt"></button>
        <div class="home-download-card" role="document">
            <button class="home-download-close" type="button" data-download-block aria-label="Close and do not show again"><i class="icofont-close-line"></i></button>
            <div class="home-download-brand"><img src="{{ asset('images/logo.jpg') }}" alt=""><span><strong>PahatudFood</strong><small>Ready, set, delivered</small></span></div>
            <div class="home-download-icon" aria-hidden="true"><span class="material-icons">smartphone</span></div>
            <span class="home-download-kicker">Continue to Google Play</span>
            <h2 id="homeDownloadTitle">Take PahatudFood with you.</h2>
            <p id="homeDownloadDescription">You are about to leave Pahatud and open Google Play to view the PahatudFood mobile app.</p>
            <div class="home-download-benefits"><span><i class="icofont-check-circled"></i> Local restaurants</span><span><i class="icofont-check-circled"></i> Easy order tracking</span></div>
            <div class="home-download-actions">
                <button class="home-download-button home-download-button-muted" type="button" data-download-block>Block</button>
                <button class="home-download-button home-download-button-primary" type="button" data-download-continue>Yes, continue <i class="icofont-arrow-right"></i></button>
            </div>
            <small class="home-download-note">Your choice is saved on this browser. This message will only appear once.</small>
        </div>
    </div>

    <a href="#" class="scrollToTop"><i class="icofont-swoosh-up"></i></a>
    @include('pages.includes.js')
    <script>
        (() => {
            const dialog = document.getElementById('homeDownloadDialog');
            if (!dialog) return;

            const preferenceKey = dialog.dataset.preferenceKey;
            const continueButton = dialog.querySelector('[data-download-continue]');
            const blockButtons = dialog.querySelectorAll('[data-download-block]');
            let pendingUrl = '';
            let previousFocus = null;

            const getPreference = () => {
                try { return window.localStorage.getItem(preferenceKey); } catch (error) { return null; }
            };
            const savePreference = (value) => {
                try { window.localStorage.setItem(preferenceKey, value); } catch (error) { /* Storage can be unavailable in private browsing. */ }
            };
            const closeDialog = () => {
                dialog.classList.remove('is-visible');
                dialog.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('home-dialog-open');
                if (previousFocus) previousFocus.focus();
            };
            const blockPrompt = () => {
                savePreference('blocked');
                pendingUrl = '';
                closeDialog();
            };

            document.addEventListener('click', (event) => {
                const link = event.target.closest('a[href*="play.google.com/store/apps/details?id=io.pahatud.com"]');
                if (!link) return;

                const preference = getPreference();
                if (preference === 'continued') return;

                event.preventDefault();
                if (preference === 'blocked') return;

                pendingUrl = link.href;
                previousFocus = link;
                dialog.classList.add('is-visible');
                dialog.setAttribute('aria-hidden', 'false');
                document.body.classList.add('home-dialog-open');
                window.setTimeout(() => continueButton.focus(), 50);
            });

            continueButton.addEventListener('click', () => {
                const destination = pendingUrl;
                savePreference('continued');
                closeDialog();
                if (destination) window.location.assign(destination);
            });
            blockButtons.forEach((button) => button.addEventListener('click', blockPrompt));
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && dialog.classList.contains('is-visible')) blockPrompt();
            });
        })();
    </script>
</body>
</html>
