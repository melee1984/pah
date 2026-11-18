@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Merchant partner / Store settings</span>
                    <h1>{{ $merchant->restaurant_name }}</h1>
                    <p>Manage account access, store availability, and ordering capabilities for this merchant.</p>
                </div>
                <div class="d-flex flex-wrap" style="gap: 10px">
                    @if ($merchant->user_id)
                        <a class="btn admin-btn-primary" href="{{ url('/merchant/aulogin/'.$merchant->user_id) }}"><i class="fas fa-external-link-alt mr-2"></i>Open merchant dashboard</a>
                    @endif
                    <a class="btn admin-btn-secondary" href="{{ route('dashboard.merchant') }}"><i class="fas fa-arrow-left mr-2"></i>All merchants</a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <merchant-settings-view
                :initial-merchant='@json($settingsMerchant)'
                :requires-review='@json($requiresReview)'
            ></merchant-settings-view>
        </div>
    </section>
</div>
@endsection
