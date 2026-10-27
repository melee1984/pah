@extends('dashboard.template.main2')

@section('content')
  
   <div class="content-wrapper admin-content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading">
          <div><span class="admin-eyebrow">Marketplace network</span><h1>Merchant Partners</h1><p>Manage restaurant availability, account access, and partner capabilities.</p></div>
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
