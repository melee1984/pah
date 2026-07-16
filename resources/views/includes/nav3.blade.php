<div class="mobile-menu">
    <nav class="mobile-header d-xl-none">
        <div class="header-logo">
            <a href="{{ route('home') }}" class="logo">
                <img src="{{ asset('images/logo-small.jpg') }}" alt="logo">
            </a>
        </div>
        <div class="header-bar">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </nav>
    <nav class="menu">
        <div class="mobile-menu-area d-xl-none">
            <div class="mobile-menu-area-inner scrollbar">
                <div class="mobile-search">
                    <input type="text" placeholder="Search Here.........">
                    <button type="submit"><i class="icofont-search-2"></i></button>
                </div>
                <ul>
                    <li>
                        <a class="active" href="#0">Home</a>
                        <ul>
                            <li><a class="active" href="index.html">Home Page One</a></li>
                            <li><a href="index-2.html">Home Page Two</a></li>
                            <li><a href="index-3.html">Home Page Three</a></li>
                            <li><a href="index-4.html">Home Page Four</a></li>
                            <li><a href="index-5.html">Home Page Five</a></li>
                        </ul>
                    </li>
                    <li><a href="about.html">About</a></li>
                    <li>
                        <a href="#0">Pages</a>
                        <ul>
                            <li>
                                <a href="#0">Category</a>
                                <ul>
                                    <li><a href="food-menu.html">Food Category</a></li>
                                    <li><a href="menu-card.html">Category style 1</a></li>
                                    <li><a href="menu-card-2.html">Category style 2</a></li>
                                </ul>
                            </li>
                            <li>
                                <a href="#0">Chef</a>
                                <ul>
                                    <li><a href="homechef.html">Home Chef</a></li>
                                    <li><a href="homechef-single.html">Home Chef Single</a></li>
                                </ul>
                            </li>
                            <li><a href="recepi-single.html">Single Recipe</a></li>
                            <li><a href="404.html">404 Page</a></li>
                            <li><a href="coming-soon.html">Coming Soon Page</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#0">Blog</a>
                        <ul>
                            <li><a href="blog.html">Blog</a></li>
                            <li><a href="blog-single.html">Blog Single</a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="#0">Shop</a>
                        <ul>
                            <li><a href="shop-page.html">Shop Page</a></li>
                            <li><a href="shop-single.html">Shop Single style-1</a></li>
                            <li><a href="shop-single-2.html">Shop Single style-2</a></li>
                            <li><a href="cart-page.html">Cart Page</a></li>
                        </ul>
                    </li>
                    <li><a href="contact-us.html">Contact</a></li>
                </ul>
                <div class="scocial-media">
                    <a href="#" class="facebook"><i class="icofont-facebook"></i></a>
                    <a href="#" class="twitter"><i class="icofont-twitter"></i></a>
                    <a href="#" class="linkedin"><i class="icofont-linkedin"></i></a>
                    <a href="#" class="vimeo"><i class="icofont-vimeo"></i></a>
                </div>
            </div>
        </div>
    </nav>
</div>
<!-- Mobile Menu Ending Here -->

<!-- header section start -->
<header class="header-section d-xl-block d-none">
    <div class="container-fluid">
        <div class="header-area">
            <div class="logo">
                <a href="{{ route('home') }}" class="logo">
                    <img src="{{ asset('images/logo-small.jpg') }}" alt="logo">
                </a>
            </div>
            <div class="main-menu">
                <menu-list></menu-list>
            </div>
            <div class="author-option">
                <div class="author-area">
                    <div class="cart-option">
                        <cart-basket-summary></cart-basket-summary>
                    </div>
                    <div class="author-account">
                        <div class="author-icon">
                            <i class="icofont-ui-user"></i>
                        </div>
                        <div class="author-select">
                            @if (Auth::check())
                            <div class="dropdown">
                                <input type="checkbox" id="userDropdown" class="dropdown-toggle-input">

                                <label for="userDropdown" class="dropdown-toggle">
                                    Hi {{ Auth::user()->firstname }}
                                    <span class="arrow">▾</span>
                                </label>

                                <div class="dropdown-menu">
                                    <a href="{{ route('profile.dashboard') }}">Profile</a>
                                    <a href="{{ route('profile.orders') }}">My Orders</a>
                                    <a href="javascript:alert('My booking is not yet available.')">My Bookings</a>
                                    <a href="javascript:alert('Settings is not yet available.')">Settings</a>
                                    <div class="divider"></div>
                                    <a href="#"
                                        onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Logout
                                    </a>
                                </div>
                            </div>

                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none;">
                                @csrf
                            </form>
                            @else
                            <span class="guest-link">
                                <a href="{{ route('profile.dashboard') }}">My Dashboard</a>
                            </span>
                            @endif
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- header section ending -->
 <style>
   
   .author-select {
    position: relative;
    display: inline-block;
    font-family: inherit;
}

/* Hide checkbox */
.dropdown-toggle-input {
    display: none;
}

/* Toggle button */
.dropdown-toggle {
    cursor: pointer;
    padding: 8px 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
}

/* Arrow */
.arrow {
    font-size: 12px;
}

/* Dropdown menu */
.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    min-width: 180px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 6px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.08);
    padding: 6px 0;
    opacity: 0;
    visibility: hidden;
    transform: translateY(5px);
    transition: all 0.2s ease;
    z-index: 1000;
}

/* Show dropdown when checked */
.dropdown-toggle-input:checked + .dropdown-toggle + .dropdown-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Menu items */
.dropdown-menu a {
    display: block;
    padding: 8px 14px;
    color: #333;
    text-decoration: none;
    font-size: 14px;
}

.dropdown-menu a:hover {
    background: #f5f5f5;
}

/* Divider */
.divider {
    height: 1px;
    background: #e5e5e5;
    margin: 6px 0;
}

/* Guest link */
.guest-link a {
    margin-left: 8px;
    text-decoration: none;
}


 </style>