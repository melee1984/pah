@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Checkout discounts</span><h1>{{ $coupon->exists ? 'Edit discount coupon' : 'Add discount coupon' }}</h1><p>This coupon will apply only to orders from your store.</p></div><ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.promotions.index') }}">Promotions</a></li><li class="breadcrumb-item active">Coupon</li></ol></div></div></section>
    <section class="content"><div class="container-fluid"><div class="merchant-settings-page"><div class="card admin-card"><div class="card-body">
        @if ($errors->any())<div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form class="merchant-settings-form" method="POST" action="{{ $coupon->exists ? route('merchant.dashboard.coupons.update', $coupon) : route('merchant.dashboard.coupons.store') }}">@csrf @if($coupon->exists) @method('PUT') @endif
            <div class="merchant-form-grid">
                <div class="form-group merchant-form-span"><label for="coupon">Coupon code</label><input class="form-control" id="coupon" name="coupon" value="{{ old('coupon', $coupon->coupon) }}" maxlength="255" placeholder="SAVE20" required></div>
                <div class="form-group"><label for="discount_value">Fixed discount value (₱)</label><input class="form-control" id="discount_value" name="discount_value" type="number" min="0.01" max="999999.99" step="0.01" value="{{ old('discount_value', $coupon->discount_value) }}"><small class="merchant-field-help">Use either a fixed value or percentage.</small></div>
                <div class="form-group"><label for="discount_percentage">Discount percentage (%)</label><input class="form-control" id="discount_percentage" name="discount_percentage" type="number" min="0.01" max="100" step="0.01" value="{{ old('discount_percentage', $coupon->discount_percentage) }}"></div>
                <div class="form-group"><label for="condition">Minimum order (₱)</label><input class="form-control" id="condition" name="condition" type="number" min="0" step="0.01" value="{{ old('condition', $coupon->condition) }}" placeholder="No minimum"></div>
                <div class="form-group"><label for="limit">Use limit</label><input class="form-control" id="limit" name="limit" type="number" min="1" value="{{ old('limit', $coupon->limit) }}" placeholder="No limit"></div>
                <div class="form-group"><label for="valid_from">Valid from</label><input class="form-control" id="valid_from" name="valid_from" type="datetime-local" value="{{ old('valid_from', $coupon->valid_from?->format('Y-m-d\TH:i')) }}"></div>
                <div class="form-group"><label for="valid_until">Valid until</label><input class="form-control" id="valid_until" name="valid_until" type="datetime-local" value="{{ old('valid_until', ($coupon->valid_until ?? $coupon->valid_at)?->format('Y-m-d\TH:i')) }}"></div>
            </div>
            <div class="merchant-publish-row mt-3"><div><strong>Available at checkout</strong><small>Turn this off to stop customers from applying the code.</small></div><label class="merchant-toggle" for="active"><input type="hidden" name="active" value="0"><input id="active" name="active" type="checkbox" value="1" @checked((bool) old('active', $coupon->active))><span><i></i></span><strong>Active</strong></label></div>
            <div class="merchant-form-actions"><a class="btn admin-btn-secondary" href="{{ route('merchant.dashboard.promotions.index') }}">Cancel</a><button class="btn admin-btn-primary" type="submit">{{ $coupon->exists ? 'Save changes' : 'Create coupon' }}</button></div>
        </form>
    </div></div></div></div></section>
</div>
@endsection
