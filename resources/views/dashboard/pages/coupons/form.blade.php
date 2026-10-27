@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Checkout discounts</span><h1>{{ $coupon->exists ? 'Edit coupon' : 'Add coupon' }}</h1><p>Choose a merchant for a store-only coupon, or leave it blank for a Pahatud-wide discount.</p></div><a class="btn admin-btn-secondary" href="{{ route('dashboard.coupons.index') }}"><i class="fas fa-arrow-left mr-2"></i>Back to coupons</a></div></div></section>
    <section class="content"><div class="container-fluid"><div class="card admin-card"><div class="card-body">
        @if ($errors->any())<div class="alert alert-danger"><strong>Please correct the highlighted fields.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        <form method="POST" action="{{ $coupon->exists ? route('dashboard.coupons.update', $coupon) : route('dashboard.coupons.store') }}">@csrf @if($coupon->exists) @method('PUT') @endif
            <div class="row">
                <div class="col-md-6 form-group"><label for="coupon">Coupon code</label><input class="form-control" id="coupon" name="coupon" value="{{ old('coupon', $coupon->coupon) }}" maxlength="255" placeholder="SAVE20" required></div>
                <div class="col-md-6 form-group"><label for="partner_id">Merchant scope</label><select class="form-control" id="partner_id" name="partner_id"><option value="">Pahatud-wide (all merchants)</option>@foreach($partners as $partner)<option value="{{ $partner->id }}" @selected((string) old('partner_id', $coupon->partner_id) === (string) $partner->id)>{{ $partner->restaurant_name }}</option>@endforeach</select></div>
                <div class="col-md-6 form-group"><label for="discount_value">Fixed discount value (₱)</label><input class="form-control" id="discount_value" name="discount_value" type="number" min="0.01" max="999999.99" step="0.01" value="{{ old('discount_value', $coupon->discount_value) }}"><small>Use either a fixed value or a percentage, not both.</small></div>
                <div class="col-md-6 form-group"><label for="discount_percentage">Discount percentage (%)</label><input class="form-control" id="discount_percentage" name="discount_percentage" type="number" min="0.01" max="100" step="0.01" value="{{ old('discount_percentage', $coupon->discount_percentage) }}"></div>
                <div class="col-md-4 form-group"><label for="condition">Minimum order (₱)</label><input class="form-control" id="condition" name="condition" type="number" min="0" step="0.01" value="{{ old('condition', $coupon->condition) }}" placeholder="No minimum"></div>
                <div class="col-md-4 form-group"><label for="valid_from">Valid from</label><input class="form-control" id="valid_from" name="valid_from" type="datetime-local" value="{{ old('valid_from', $coupon->valid_from?->format('Y-m-d\TH:i')) }}"></div>
                <div class="col-md-4 form-group"><label for="valid_until">Valid until</label><input class="form-control" id="valid_until" name="valid_until" type="datetime-local" value="{{ old('valid_until', ($coupon->valid_until ?? $coupon->valid_at)?->format('Y-m-d\TH:i')) }}"></div>
                <div class="col-md-6 form-group"><label for="limit">Use limit</label><input class="form-control" id="limit" name="limit" type="number" min="1" value="{{ old('limit', $coupon->limit) }}" placeholder="No limit"></div>
                <div class="col-md-6 form-group d-flex align-items-center"><div class="form-check mt-3"><input type="hidden" name="active" value="0"><input class="form-check-input" id="active" name="active" type="checkbox" value="1" @checked((bool) old('active', $coupon->active))><label class="form-check-label" for="active">Active and available at checkout</label></div></div>
            </div>
            <div class="d-flex justify-content-end mt-4"><a class="btn admin-btn-secondary mr-2" href="{{ route('dashboard.coupons.index') }}">Cancel</a><button class="btn admin-btn-primary" type="submit">{{ $coupon->exists ? 'Save changes' : 'Create coupon' }}</button></div>
        </form>
    </div></div></div></section>
</div>
@endsection
