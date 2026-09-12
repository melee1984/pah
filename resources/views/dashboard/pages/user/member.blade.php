@extends('dashboard.template.main2')

@section('content')
  
   <div class="content-wrapper admin-content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading">
          <div><span class="admin-eyebrow">Customer directory</span><h1>Members</h1><p>Browse registered Pahatud customer accounts.</p></div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
              <member-view></member-view>
          </div>
        </div>
      </div>
  </section>
</div>



@endsection
