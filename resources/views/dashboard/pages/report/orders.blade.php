@extends('dashboard.template.main')

@section('content')
  
   <div class="content-wrapper admin-content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading"><div><span class="admin-eyebrow">Revenue reporting</span><h1>Sales Report</h1><p>Review merchant sales, commission, delivery fees, and net revenue.</p></div></div>
      </div><!-- /.container-fluid -->
    </section>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-md-12">
              <report-view></report-view>
          </div>
        </div>
      </div>
  </section>
</div>



@endsection
