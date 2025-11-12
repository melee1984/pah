@extends('merchant.template.main')

@section('content')
  	
  	<div class="content-wrapper" style="min-height: 1416.81px;">

  	<section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Previous Orders</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ URL::to('merchant/dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Previous Orders</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

  	<section class="content">
      	<div class="container-fluid">
			<div class="row">
				<merchant-order-view></merchant-order-view>
			</div>
		</div>
	</section>
</div>

@endsection