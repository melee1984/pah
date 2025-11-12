<div class="banner">
	<div class="banner-image d-none d-sm-block"> </div>
	<div class="container">
		<h1>Order, Food and <br>We Deliver</h1>
		
		<div class="banner-area">
		 <div class="banner-content">
			<div class="row">
				<div class="col-xl-6 col-12">
					<div class="banner-content">
						<form action="{{ route('search') }}" method="post">
							@csrf()
							<input type="text" name="s" placeholder="Search restaurants, food or city">
							<button type="submit"><span class="material-icons">search</span></button>
						</form>
					</div>
				</div>
			</div>

		</div> 
	</div>
	<p class="download">
			Enjoy our 15% discount on selected local merchant partner. <br>Stay at home and we will do the hassle for you.
	</p>	
	<a href="https://play.google.com/store/apps/details?id=io.pahatud.com" target="_blank">
		<img src="{{asset('images/google-play.png')}}" alt="" width="200px">
	</a>
	</div>
</div>