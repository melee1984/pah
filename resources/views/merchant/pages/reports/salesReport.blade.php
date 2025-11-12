@extends('merchant.template.main')

@section('content')
    
    <div class="content-wrapper" style="min-height: 1416.81px;">

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Sales Report</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="{{ URL::to('merchant/dashboard') }}">Home</a></li>
              <li class="breadcrumb-item active">Sales Report</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
           <div class="col-md-12">
             <div class="alert alert-info" role="alert">
              This will generate soon. 
            </div> 
          </div>
        </div>
      </div>
  </section>
</div>

@endsection