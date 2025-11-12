@extends('dashboard.template.main2')

@section('content')
  
   <div class="content-wrapper" style="min-height: 1416.81px;">

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Add new Booking</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ URL::to('merchant/dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Add new</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <section class="content">
      <div class="container-fluid">
          <booking-add-form></booking-add-form>
      </div>
  </section>
</div>



@endsection

