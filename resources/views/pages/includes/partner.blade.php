<!-- top Restaurants section start here -->
<section class="restaurant-section padding-tb">
	<div class="container">
		<div class="section-header">
			<h3>Our Partners</h3>
			<p>*</p>
		</div>
		<div class="section-wrapper">
			<div class="top-restaurant">

				@foreach($partners as $partner)
					<div class="restaurant-item">
						<div class="restaurant-inner">
							<div class="restaurant-thumb">
								<a href="{{ route('restaurant.view', $partner) }}"><img src="{{ $partner->img }}" alt="{{ $partner->restaurant_name }}"></a>
							</div>
						</div>
					</div>
				@endforeach
				<!-- 
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="images/partners/pachoos.png" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="uploads/user/16/2021-03-22-popskie.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="images/partners/m&m.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="images/partners/sugar-home-creations.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="images/partners/ken-cakeshoppe.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="images/partners/mces-tea-vibes-corner.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="uploads/user/17/2021-03-23-chillaxburger.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="uploads/user/21/2021-03-24-beans-baking.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div>
				<div class="restaurant-item">
					<div class="restaurant-inner">
						<div class="restaurant-thumb">
							<a href="#"><img src="uploads/user/15/2021-03-22-cbs.jpg" alt="restaurant"></a>
						</div>
					</div>
				</div> -->
			</div>
		</div>
	</div>
</section>
<!-- top Restaurants section ending here -->