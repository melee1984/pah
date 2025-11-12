@extends('templates.empty')
@section('title', 'Booking')

@section('content')
<section class="about about-page padding-tb">
    <div class="container" id="historybookings">
    	<div class="introname">Hello {{ ucfirst(Auth::User()->firstname) }}! How are you today? </div> 
    	<h3>My Bookings</h3>
	   	<hr>
    	<div class="row">
    		<div class="col-md-12">
    			<div class="alert alert-info" role="alert">
					  Your booking is empty
				</div>
    		</div>


	   		<div class="col-md-4">
	   			<!-- <ul class="list-group list-group-flush">
	   				<li class="list-group-item list-group-item-action"><a href="">01.20.2021 - Job Order 10</a></li>
	   			</ul> -->
	   		</div>
	   		<div class="col-md-8">
	   				
	   				

	   				<!-- <div class="row mb-4">
					  <div class="col-sm-6">
					    <div class="card">
					      <div class="card-body">
					        <h5 class="card-title">Pickup Information</h5>
					        <p class="card-text">
					        	<strong>Pickup Address:</strong> <br> 171 gumame last kasilak bucana, Davao City <br/><hr>
					        	<strong>Sender: </strong> Leslie Parba <br>
					        	<strong>Mobile: </strong> 091234567 
					        	<hr>
					        	<strong>Item: </strong> <br>
					        	<p>171 gumame last kasilak bucana, Davao City 171 gumame last kasilak bucana, Davao City</p>
					        </p>
					        
					      </div>
					    </div>
					  </div>
					  <div class="col-sm-6">
					    <div class="card">
					      <div class="card-body">
					        <h5 class="card-title">Drop-off Information</h5>
					         <p class="card-text">
					        	<strong>Drop-off Address:</strong> <br> 171 gumame last kasilak bucana, Davao City <br/><hr>
					        	<strong>Receiver: </strong> Leslie Parba <br>
					        	<strong>Mobile: </strong> 091234567
					        </p>
					      </div>
					    </div>
					  </div>
					</div> -->

					<!-- <div class="card mb-2">
					  <div class="card-header">
					    Payments 
					  </div>
					  <div class="card-body">
					    <p class="card-text">
					    	<div class="row">
								<div class="col-md-8">
									<p>Pickup Date/Time: <strong>January 10, 2021 @ 10am</strong></p>
						        	<p>Errand Type: <strong>Pickup/Delivery</strong></p>
						        	<p><strong>COD: 2000.00</strong></p>
					        		<p><strong>Distance:</strong> 10km</p>
					    			<p><strong>Delivery Fee:</strong> 100.00 PHP</p>
					    			<p><strong>Collect delivery fee: </strong>Receiver will pay the delivery fee</p>
					    			<p><strong>Rider:</strong> <a href="">Leslie Parba</a></p>
					    		</div>
						    	<div class="col-md-4">
						    		<p><strong>Total Amount: </strong> 2000.00 PHP <br></p>
						        	<p><strong>Status: </strong> Delivered <br></p>
						    	</div>
					    	</div>
					    </p>
					  </div>
					</div>

					<div class="row mt-4">
						<div class="col-sm-6">
			    			<a href="javascript:void(0)" class="btn btn-secondary btn-pahatud btn-block">Re-booking</a> 
						</div>
						<div class="col-sm-6 text-right">
							<a href="javascript:void(0)" class="btn" disabled>Cancel</a> 
						</div>
					</div> -->

					

	   		</div>
	   	</div> 
    </div>
</section>


@endsection