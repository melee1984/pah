@extends('merchant.template.main')

@section('content')
  	
  	<div class="content-wrapper" style="min-height: 1416.81px;">

  	<section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Profile</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Home</a></li>
              <li class="breadcrumb-item active">User Profile</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

  	<section class="content">
      <div class="container-fluid">
		<div class="row">
			<div class="col-md-3">

            <!-- Profile Image -->
            <div class="card card-primary card-outline">
              <div class="card-body box-profile">

                <h3 class="profile-username text-center">CATEGORY</h3>
				
				<ul class="list-group list-group-unbordered mb-3">
                  <li class="list-group-item">
                    <b>CAEGORY 1</b> <a class="float-right">1,322</a>
                  </li>
                  <li class="list-group-item">
                    <b>CAEGORY 1</b> <a class="float-right">543</a>
                  </li>
                  <li class="list-group-item">
                    <b>CAEGORY 1</b> <a class="float-right">13,287</a>
                  </li>
                </ul>

                <a href="#" class="btn btn-pahatud btn-block"><b>ADD NEW CATEGORY</b></a>
              </div>
              <!-- /.card-body -->
            </div>
            <!-- /.card -->

          </div>
			<div class="col-md-9">
				<div class="row">
	          <div class="col-12">
	            <div class="card">
	              <div class="card-header">
	                <h3 class="card-title">PRODUCTS</h3>

	                <div class="card-tools">
	                  <div class="input-group input-group-sm" style="width: 150px;">
	                    <input type="text" name="table_search" class="form-control float-right" placeholder="Search">

	                    <div class="input-group-append">
	                      <button type="submit" class="btn btn-default"><i class="fas fa-search"></i></button>
	                    </div>
	                  </div>
	                </div>
	              </div>
	              <!-- /.card-header -->
	              <div class="card-body table-responsive p-0">
	                <table class="table table-hover text-nowrap">
	                  <thead>
	                    <tr>
	                      <th>ID</th>
	                      <th>User</th>
	                      <th>Date</th>
	                      <th>Status</th>
	                      <th>Reason</th>
	                    </tr>
	                  </thead>
	                  <tbody>
	                    <tr>
	                      <td>183</td>
	                      <td>John Doe</td>
	                      <td>11-7-2014</td>
	                      <td><span class="tag tag-success">Approved</span></td>
	                      <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
	                    </tr>
	                    <tr>
	                      <td>219</td>
	                      <td>Alexander Pierce</td>
	                      <td>11-7-2014</td>
	                      <td><span class="tag tag-warning">Pending</span></td>
	                      <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
	                    </tr>
	                    <tr>
	                      <td>657</td>
	                      <td>Bob Doe</td>
	                      <td>11-7-2014</td>
	                      <td><span class="tag tag-primary">Approved</span></td>
	                      <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
	                    </tr>
	                    <tr>
	                      <td>175</td>
	                      <td>Mike Doe</td>
	                      <td>11-7-2014</td>
	                      <td><span class="tag tag-danger">Denied</span></td>
	                      <td>Bacon ipsum dolor sit amet salami venison chicken flank fatback doner.</td>
	                    </tr>
	                  </tbody>
	                </table>
	              </div>
	              <!-- /.card-body -->
	            </div>
	            <!-- /.card -->
	          </div>
	        </div>
			</div>
			</div>
		</div>
	</section>
</div>

@endsection