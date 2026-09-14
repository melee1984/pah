@extends('merchant.template.main')

@section('content')
    <div class="content-wrapper admin-content-wrapper"><section class="content-header admin-page-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Restaurant account</span><h1>Restaurant application</h1><p>Review your business details and manage approval documents.</p></div></div></div></section><section class="content"><div class="container-fluid application-page">
        @include('restaurant.application-details', ['agentView' => false])
    </div></section></div>
@endsection
