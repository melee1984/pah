@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Store marketing</span><h1>Promotions</h1><p>Create checkout discount coupons and manage promotional banners for your store.</p></div>
                <ol class="breadcrumb"><li class="breadcrumb-item"><a href="{{ route('merchant.dashboard.index') }}">Dashboard</a></li><li class="breadcrumb-item active">Promotions</li></ol>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="merchant-settings-page merchant-promotions-page">
                @if (session('success'))
                    <div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>
                @endif

                <div class="card admin-card dashboard-data-card mb-4">
                    <div class="admin-card-header merchant-settings-card-header">
                        <div><h2>Checkout discount coupons</h2><p>{{ number_format($coupons->total()) }} {{ Str::plural('coupon', $coupons->total()) }} for your store. Customers can apply active codes during checkout.</p></div>
                        <div class="merchant-settings-toolbar"><a class="btn admin-btn-primary" href="{{ route('merchant.dashboard.coupons.create') }}"><i class="fas fa-plus mr-2"></i>Add coupon</a></div>
                    </div>
                    @if ($coupons->isEmpty())
                        <div class="admin-empty-state"><span><i class="fas fa-ticket-alt"></i></span><h3>No discount coupons yet</h3><p>Create a fixed-value or percentage discount for orders from your store.</p><a class="btn admin-btn-primary mt-3" href="{{ route('merchant.dashboard.coupons.create') }}">Add coupon</a></div>
                    @else
                        <div class="card-body table-responsive p-0">
                            <table class="table dashboard-data-table merchant-settings-table">
                                <thead><tr><th>Code</th><th>Discount</th><th>Validity</th><th>Minimum / limit</th><th>Status</th><th class="text-right">Actions</th></tr></thead>
                                <tbody>
                                @foreach ($coupons as $coupon)
                                    <tr>
                                        <td><strong>{{ $coupon->coupon }}</strong><small>Store-specific</small></td>
                                        <td><strong>{{ $coupon->discount_percentage !== null ? rtrim(rtrim(number_format($coupon->discount_percentage, 2), '0'), '.').'%' : '₱'.number_format($coupon->discount_value, 2) }}</strong></td>
                                        <td><strong>{{ $coupon->valid_from?->format('M d, Y · g:i A') ?? 'Starts immediately' }}</strong><small>{{ ($coupon->valid_until ?? $coupon->valid_at) ? 'Ends '.($coupon->valid_until ?? $coupon->valid_at)->format('M d, Y · g:i A') : 'No expiry' }}</small></td>
                                        <td><strong>{{ $coupon->condition ? '₱'.number_format($coupon->condition, 2).' minimum' : 'No minimum' }}</strong><small>{{ number_format($coupon->usage_count) }} used{{ $coupon->limit ? ' / '.number_format($coupon->limit).' limit' : ' / no limit' }}</small></td>
                                        <td><span class="admin-status {{ $coupon->active ? 'admin-status-active' : 'admin-status-inactive' }}">{{ $coupon->active ? 'Active' : 'Inactive' }}</span></td>
                                        <td><div class="promotion-actions justify-content-end"><a class="btn admin-btn-secondary promotion-action-button" href="{{ route('merchant.dashboard.coupons.edit', $coupon) }}">Edit</a><form method="POST" action="{{ route('merchant.dashboard.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?');">@csrf @method('DELETE')<button class="btn promotion-action-button promotion-action-danger" type="submit">Delete</button></form></div></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="merchant-settings-pagination">{{ $coupons->links('pagination::bootstrap-4') }}</div>
                    @endif
                </div>

                <div class="card admin-card dashboard-data-card">
                    <div class="admin-card-header merchant-settings-card-header">
                        <div><h2>Your promotions</h2><p>{{ number_format($promotions->total()) }} {{ Str::plural('promotion', $promotions->total()) }} submitted. New and edited promotions require administrator approval.</p></div>
                        <div class="merchant-settings-toolbar"><a class="btn admin-btn-primary" href="{{ route('merchant.dashboard.promotions.create') }}"><i class="fas fa-plus mr-2"></i>Add promotion</a></div>
                    </div>

                    @if ($promotions->isEmpty())
                        <div class="admin-empty-state"><span><i class="fas fa-bullhorn"></i></span><h3>No promotions yet</h3><p>Create your first promotional banner and submit it for approval.</p><a class="btn admin-btn-primary mt-3" href="{{ route('merchant.dashboard.promotions.create') }}">Add promotion</a></div>
                    @else
                        <div class="card-body table-responsive p-0">
                            <table class="table dashboard-data-table merchant-settings-table merchant-promotions-table">
                                <thead><tr><th>Promotion</th><th>Schedule</th><th>Approval</th><th>Display</th><th class="text-right">Actions</th></tr></thead>
                                <tbody>
                                @foreach ($promotions as $promotion)
                                    <tr>
                                        <td><div class="merchant-promotion-identity"><img src="{{ $promotion->image_url }}" alt="{{ $promotion->name }}"><span><strong>{{ $promotion->name }}</strong><small>{{ $promotion->subtitle ?: 'No subtitle provided' }}</small></span></div></td>
                                        <td><strong>{{ $promotion->starts_at?->format('M d, Y · g:i A') ?? 'Starts immediately' }}</strong><small>{{ $promotion->ends_at ? 'Ends '.$promotion->ends_at->format('M d, Y · g:i A') : 'No end date' }}</small></td>
                                        <td>@if ($promotion->approval_status === \App\PartnerPromotion::APPROVAL_APPROVED)<span class="admin-status admin-status-active">Approved</span>@elseif ($promotion->approval_status === \App\PartnerPromotion::APPROVAL_REJECTED)<span class="admin-status admin-status-inactive">Rejected</span>@else<span class="admin-status admin-status-pending">Pending approval</span>@endif</td>
                                        <td>@if (! $promotion->active)<span class="admin-status admin-status-inactive">Inactive</span>@elseif ($promotion->approval_status !== \App\PartnerPromotion::APPROVAL_APPROVED)<span class="admin-status admin-status-pending">Not published</span>@elseif ($promotion->starts_at && $promotion->starts_at->isFuture())<span class="admin-status admin-status-pending">Scheduled</span>@elseif ($promotion->ends_at && $promotion->ends_at->isPast())<span class="admin-status admin-status-inactive">Expired</span>@else<span class="admin-status admin-status-active">Live</span>@endif</td>
                                        <td><div class="promotion-actions justify-content-end"><a class="btn admin-btn-secondary promotion-action-button" href="{{ route('merchant.dashboard.promotions.edit', $promotion) }}">Edit</a><form method="POST" action="{{ route('merchant.dashboard.promotions.destroy', $promotion) }}" onsubmit="return confirm('Delete this promotion? This cannot be undone.');">@csrf @method('DELETE')<button class="btn promotion-action-button promotion-action-danger" type="submit">Delete</button></form></div></td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="merchant-settings-pagination">{{ $promotions->links('pagination::bootstrap-4') }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>
</div>
@endsection
