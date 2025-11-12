@extends('dashboard.template.main2')

@section('content')
  
   <div class="content-wrapper" style="min-height: 1416.81px;">

    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Merchant</h1>
          </div>
          <div class="col-sm-6">
          
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
              <merchant-view></merchant-view>
          </div>
        </div>
      </div>
  </section>
</div>



@endsection

