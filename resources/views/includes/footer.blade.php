@if (!Request::is('restaurant/*') && !Request::is('restaurants'))
    <div id="fb-root"></div>
    <div class="fb-customerchat" attribution="setup_tool" page_id="106782101100929" theme_color="#ef3b35"
        logged_in_greeting="Hi! How can we help?" logged_out_greeting="Hi! How can we help?"></div>
@endif

<footer class="public-footer">
    <div class="container">
        <div class="public-footer-callout">
            <div>
                <span>Ready, set, delivered</span>
                <h2>Good food and local favorites, delivered with care.</h2>
            </div>
            <a href="{{ route('home') }}#restaurant-partners">Explore restaurants <i class="icofont-arrow-right"></i></a>
        </div>
        <div class="public-footer-grid">
            <div class="public-footer-brand">
                <a href="{{ route('home') }}" aria-label="Pahatud home"><img src="{{ asset('images/logo.jpg') }}" alt="PahatudFood"><strong>PahatudFood</strong></a>
                <p>Your local delivery partner for restaurants, merchants, and communities across Davao.</p>
                <div class="public-footer-social">
                    <a href="https://www.facebook.com/pahatudDelivery" target="_blank" rel="noopener noreferrer" aria-label="Pahatud on Facebook"><i class="icofont-facebook"></i></a>
                    <a href="mailto:info@pahatud.com" aria-label="Email Pahatud"><i class="icofont-email"></i></a>
                </div>
            </div>
            <div class="public-footer-links"><strong>Company</strong><a href="{{ route('aboutus') }}">About us</a><a href="{{ route('contactus') }}">Contact us</a><a href="{{ route('agent.register') }}">Become an agent</a><a href="{{ route('home') }}#become-a-partner">Partner with us</a></div>
            <div class="public-footer-links"><strong>Trust &amp; safety</strong><a href="{{ route('privacypolicy') }}">Privacy policy</a><a href="{{ route('termsofuse') }}">Terms of use</a><a href="{{ route('fraudprevention') }}">Fraud prevention</a></div>
            <div class="public-footer-app"><strong>Order on the go</strong><p>Browse restaurants and manage deliveries from your phone.</p><a href="https://play.google.com/store/apps/details?id=io.pahatud.com" target="_blank" rel="noopener noreferrer"><img src="{{ asset('images/google-play.png') }}" alt="Get Pahatud on Google Play"></a></div>
        </div>
        <div class="public-footer-bottom"><span>&copy; {{ date('Y') }} Pahatud. All rights reserved.</span><span>Made for the Davao community.</span></div>
    </div>
</footer>
