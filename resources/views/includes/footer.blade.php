@if (!Request::is('restaurant/*') && !Request::is('restaurants'))
      <div id="fb-root"></div>
       <script>
        window.fbAsyncInit = function() {
          FB.init({
            xfbml            : true,
            version          : 'v8.0'
          });
        };

        (function(d, s, id) {
        var js, fjs = d.getElementsByTagName(s)[0];
        if (d.getElementById(id)) return;
        js = d.createElement(s); js.id = id;
        js.src = 'https://connect.facebook.net/en_US/sdk/xfbml.customerchat.js';
        fjs.parentNode.insertBefore(js, fjs);
      }(document, 'script', 'facebook-jssdk'));</script> 

      <!-- Your Chat Plugin code -->
      <div class="fb-customerchat"
        attribution=setup_tool
        page_id="106782101100929"
  theme_color="#fa3c4c"
  logged_in_greeting="Hey whats up? can we help you? "
  logged_out_greeting="Hey whats up? can we help you? ">
      </div>

@endif

<!-- Footer Section Start Here -->
<footer class="footer">
	
	<div class="bg-shape-style"></div>

	

	<div class="container">
		<div class="footer-top">

			

			<div class="footer-area text-center">
				<div class="footer-logo">
					<!-- <a href="{{ URL::to('/') }}"><img src="{{ asset('images/logo-medium.png') }}" alt="footer-logo"></a> -->
				
				<a href="https://play.google.com/store/apps/details?id=io.pahatud.com">
					<img src="{{ asset('images/google-play.png') }}" class="img-responsive" width="60%">
				</a>
			
				</div>
				<div class="scocial-media">
					<a href="https://www.facebook.com/pahatudDelivery" class="facebook"><i class="icofont-facebook"></i></a>
				</div>
				<div class="footer-menu d-none d-sm-block d-sm-none d-md-block">
					<ul>
						<li><a href="{{ URL::to('/') }}">Home</a></li>
						<li><a href="{{ route('contactus') }}">Contact</a></li>
						<li><a href="{{ route('privacypolicy') }}">Privacy Policy</a></li>
						<li><a href="{{ route('termsofuse') }}">Terms of Use</a></li>
						<li><a href="{{ route('fraudprevention') }}">Fraud Prevention</a></li>
						<!-- <li><a href="{{ route('paymentmethod') }}">Payment Method</a></li> -->
					</ul>
				</div>
			</div>
		</div>
		<div class="footer-bottom text-center">
			<p>&copy; <?= date('Y') ?> <a href="#"><span>Alright Reserved</span></a> Pahatud.com.</p>
		</div>
	</div>
</footer>
<!-- Footer Section Ending Here