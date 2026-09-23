@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div><span class="admin-eyebrow">Checkout discounts</span><h1>Discount coupons</h1><p>Manage Pahatud-wide and merchant-specific coupon codes used at checkout.</p></div>
                <a class="btn admin-btn-primary" href="{{ route('dashboard.coupons.create') }}"><i class="fas fa-plus mr-2"></i>Add coupon</a>
            </div>
        </div>
    </section>
    <section class="content">
        <div class="container-fluid">
            @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
            <div class="card admin-card">
                <div class="admin-card-header"><div><h2>Coupons</h2><p>{{ number_format($coupons->total()) }} {{ Str::plural('coupon', $coupons->total()) }}</p></div></div>
                @if ($coupons->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-ticket-alt"></i></span><h3>No coupons yet</h3><p>Create a Pahatud-wide coupon or one for a specific merchant.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table">
                            <thead><tr><th>Code</th><th>Discount</th><th>Scope</th><th>Validity</th><th>Minimum / limit</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                            @foreach ($coupons as $coupon)
                                <tr>
                                    <td><strong>{{ $coupon->coupon }}</strong></td>
                                    <td><strong>{{ $coupon->discount_percentage !== null ? rtrim(rtrim(number_format($coupon->discount_percentage, 2), '0'), '.').'%' : '₱'.number_format($coupon->discount_value, 2) }}</strong></td>
                                    <td><strong>{{ $coupon->partner?->restaurant_name ?? 'Pahatud-wide' }}</strong><small>{{ $coupon->partner_id ? 'Merchant-specific' : 'All merchant partners' }}</small></td>
                                    <td><strong>{{ $coupon->valid_from?->format('M d, Y · g:i A') ?? 'Immediately' }}</strong><small>Until {{ ($coupon->valid_until ?? $coupon->valid_at)?->format('M d, Y · g:i A') ?? 'no expiry' }}</small></td>
                                    <td><strong>{{ $coupon->condition ? '₱'.number_format($coupon->condition, 2).' minimum' : 'No minimum' }}</strong><small>{{ $coupon->limit ? number_format($coupon->limit).' use limit' : 'No use limit' }}</small></td>
                                    <td><span class="admin-status {{ $coupon->active ? 'admin-status-active' : 'admin-status-inactive' }}">{{ $coupon->active ? 'Active' : 'Inactive' }}</span></td>
                                    <td><div class="promotion-actions"><a class="btn admin-btn-secondary promotion-action-button" href="{{ route('dashboard.coupons.edit', $coupon) }}">Edit</a><form method="POST" action="{{ route('dashboard.coupons.destroy', $coupon) }}" onsubmit="return confirm('Delete this coupon?');">@csrf @method('DELETE')<button class="btn promotion-action-button promotion-action-danger" type="submit">Delete</button></form></div></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $coupons->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
