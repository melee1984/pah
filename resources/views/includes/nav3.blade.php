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

                <div class="author-account public-account-menu">
                    @if (Auth::check())
                        <div class="dropdown">
                            <input type="checkbox" id="userDropdown" class="dropdown-toggle-input">
                            <label for="userDropdown" class="dropdown-toggle">
                                <span class="public-account-avatar" aria-hidden="true">{{ strtoupper(substr(Auth::user()->firstname, 0, 1)) }}</span>
                                <span>Hi, {{ Auth::user()->firstname }}</span>
                                <i class="icofont-rounded-down" aria-hidden="true"></i>
                            </label>

                            <div class="dropdown-menu">
                                <a href="{{ route('profile.dashboard') }}">Profile</a>
                                <a href="{{ route('profile.orders') }}">My orders</a>
                                <a href="javascript:alert('My booking is not yet available.')">My bookings</a>
                                <a href="javascript:alert('Settings is not yet available.')">Settings</a>
                                <div class="divider"></div>
                                <a href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a>
                            </div>
                        </div>

                        <form id="logout-form" action="{{ route('logout') }}" method="POST" hidden>
                            @csrf
                        </form>
                    @else
                        <a href="{{ route('profile.dashboard') }}" class="public-dashboard-link">
                            <span class="public-account-avatar" aria-hidden="true"><i class="icofont-ui-user"></i></span>
                            <span>My dashboard</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
