<div class="public-mobile-shell d-xl-none">
    <div class="container">
        <nav class="public-mobile-header" aria-label="Mobile navigation">
            <a href="{{ route('home') }}" class="public-brand" aria-label="Pahatud home">
                <svg class="public-brand-mark" viewBox="0 0 48 48" width="46" height="46" aria-hidden="true" focusable="false">
                    <rect width="48" height="48" rx="11" fill="currentColor"/>
                    <path fill="#fff" fill-rule="evenodd" d="M14 9h14.25C35.84 9 41 13.7 41 20.55 41 27.4 35.84 32 28.25 32H23v7h-9V9Zm9 8v7h5.1c2.45 0 3.9-1.28 3.9-3.5S30.55 17 28.1 17H23Z" clip-rule="evenodd"/>
                </svg>
                <span class="public-brand-copy"><strong>PahatudFood</strong><small>Local food delivery</small></span>
            </a>

            <div class="public-mobile-actions">
                @include('includes.customer-account-menu')
                <div class="public-header-cart">
                    <cart-basket-summary></cart-basket-summary>
                </div>
                <details class="public-mobile-menu">
                    <summary aria-label="Open navigation menu">
                        <span></span><span></span><span></span>
                    </summary>
                    <div class="public-mobile-menu-panel">
                        <a href="{{ route('home') }}">Home</a>
                        <a href="/be-partner">Be our partner</a>
                        <a href="/restaurants">Restaurants</a>
                        <a href="/flowerstore">Flower store</a>
                        <a href="/request/booking">Delivery rates</a>
                        <div class="public-mobile-menu-divider"></div>
                        <a href="{{ route('profile.dashboard') }}" class="public-mobile-account">
                            <i class="icofont-ui-user" aria-hidden="true"></i>
                            {{ Auth::check() ? 'My account' : 'My dashboard' }}
                        </a>
                    </div>
                </details>
            </div>
        </nav>
    </div>
</div>

<header class="header-section public-site-header d-xl-block d-none">
    <div class="container">
        <div class="header-area public-header-inner">
            <a href="{{ route('home') }}" class="public-brand" aria-label="Pahatud home">
                <svg class="public-brand-mark" viewBox="0 0 48 48" width="46" height="46" aria-hidden="true" focusable="false">
                    <rect width="48" height="48" rx="11" fill="currentColor"/>
                    <path fill="#fff" fill-rule="evenodd" d="M14 9h14.25C35.84 9 41 13.7 41 20.55 41 27.4 35.84 32 28.25 32H23v7h-9V9Zm9 8v7h5.1c2.45 0 3.9-1.28 3.9-3.5S30.55 17 28.1 17H23Z" clip-rule="evenodd"/>
                </svg>
                <span class="public-brand-copy"><strong>PahatudFood</strong><small>Local food delivery</small></span>
            </a>

            <nav class="main-menu public-primary-nav" aria-label="Primary navigation">
                <menu-list></menu-list>
            </nav>

            <div class="author-option public-header-utilities">
                <div class="public-header-cart">
                    <cart-basket-summary></cart-basket-summary>
                </div>

                @include('includes.customer-account-menu')
            </div>
        </div>
    </div>
</header>
