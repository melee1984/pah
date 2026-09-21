@extends('merchant.template.main')

@section('content')
    
    <div class="content-wrapper admin-content-wrapper">

    <section class="content-header">
      <div class="container-fluid">
        <div class="admin-page-heading">
          <div><span class="admin-eyebrow">Store settings</span><h1>Profile</h1><p>Keep your public business details, branding, and catalog tags up to date.</p></div>
          <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Profile</li></ol>
        </div>
      </div>
    </section>

    <section class="content">
      <div class="container-fluid">
        <merchant-profile-view></merchant-profile-view>
      </div>
  </section>
</div>

@endsection
