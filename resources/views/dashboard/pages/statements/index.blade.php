@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header">
        <div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Merchant billing</span><h1>Statements of account</h1><p>Find completed orders and issue an immutable statement to one merchant.</p></div></div></div>
    </section>

    <section class="content"><div class="container-fluid statement-page">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><strong>The statement was not created.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="card admin-card mb-4">
            <div class="admin-card-header"><div><h2>1. Find eligible orders</h2><p>Only delivered or completed orders not yet included in another statement are shown.</p></div></div>
            <form class="dashboard-filter-bar statement-filter-bar" method="GET" action="{{ route('dashboard.statements.index') }}">
                <div><label for="merchant">Merchant</label><select id="merchant" name="merchant" class="form-control"><option value="">All merchants</option>@foreach ($merchants as $merchant)<option value="{{ $merchant->id }}" @selected((string) ($filters['merchant'] ?? '') === (string) $merchant->id)>{{ $merchant->restaurant_name }}</option>@endforeach</select></div>
                <div><label for="search">Order number</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Order number or ID"></div>
                <div class="statement-date-range-field"><label for="statement-date-range">Completed date range</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text"><i class="far fa-calendar-alt"></i></span></div><input id="statement-date-range" name="date_range" type="text" class="form-control" value="{{ $filters['date_range'] ?? '' }}" placeholder="Select start and end dates" autocomplete="off"></div></div>
                <div class="dashboard-filter-actions"><a class="btn admin-btn-secondary" href="{{ route('dashboard.statements.index') }}">Clear</a><button class="btn admin-btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Search</button></div>
            </form>
        </div>

        <form method="POST" action="{{ route('dashboard.statements.store') }}" id="statement-create-form">
            @csrf
            <input type="hidden" name="partner_id" value="{{ $filters['merchant'] ?? '' }}">
            <div class="card admin-card dashboard-data-card mb-4">
                <div class="admin-card-header">
                    <div><h2>2. Select orders</h2><p>@if($filters['merchant'] ?? null)Choose the records to snapshot into this merchant's statement.@else Select a merchant above before choosing orders.@endif</p></div>
                    @if($filters['merchant'] ?? null)<label class="statement-select-all"><input type="checkbox" id="select-all-orders"> Select all on this page</label>@endif
                </div>
                <div class="table-responsive">
                    <table class="table dashboard-data-table statement-order-table">
                        <thead><tr><th></th><th>Completed</th><th>Merchant / order</th><th>Order type</th><th>Status</th><th>Total</th><th>Pahatud commission</th><th>Delivery fee</th><th>Convenience fee</th><th>VAT</th><th>Merchant net</th></tr></thead>
                        <tbody>
                        @forelse ($orders as $order)
                            @php
                                $summary = $order->statement_summary;
                                $money = fn ($key) => (float) str_replace(',', '', (string) ($summary[$key] ?? 0));
                                $canSelect = (string) ($filters['merchant'] ?? '') === (string) $order->partner_id;
                            @endphp
                            <tr>
                                <td>@if($canSelect)<input class="statement-order-checkbox" type="checkbox" name="order_ids[]" value="{{ $order->id }}" @checked(in_array($order->id, old('order_ids', []))) aria-label="Select order {{ $order->cart?->order_no ?? $order->id }}">@else<span class="text-muted">—</span>@endif</td>
                                <td><strong>{{ optional($order->statement_completed_at)->format('M d, Y') }}</strong><small>{{ optional($order->statement_completed_at)->format('g:i A') }}</small></td>
                                <td><strong>{{ $order->partner?->restaurant_name ?? 'Unavailable' }}</strong><small>Order #{{ $order->cart?->order_no ?? $order->id }}</small></td>
                                @php($orderType = $order->cart?->fulfillment_type ?: 'delivery')
                                <td><span class="order-option-badge is-{{ $orderType }}"><i class="fas {{ $orderType === 'pickup' ? 'fa-shopping-bag' : ($orderType === 'dine_in' ? 'fa-utensils' : 'fa-motorcycle') }}" aria-hidden="true"></i>{{ $orderType === 'dine_in' ? 'Dine-in' : ucfirst($orderType) }}</span></td>
                                <td><span class="dashboard-status-pill is-success">{{ $order->orderStatus?->title ?? 'Completed' }}</span></td>
                                <td class="dashboard-money">₱{{ number_format($money('total'), 2) }}</td>
                                <td class="dashboard-money">₱{{ number_format($money('total_comm'), 2) }}</td>
                                <td class="dashboard-money">₱{{ number_format($money('delivery_fee'), 2) }}</td>
                                <td class="dashboard-money">₱{{ number_format($money('convenience_fee'), 2) }}</td>
                                <td class="dashboard-money">₱{{ number_format($money('vat_amount'), 2) }}</td>
                                <td class="dashboard-money"><strong>₱{{ number_format($money('total') - $money('total_comm'), 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="dashboard-table-empty">No unprocessed completed orders match this search.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer statement-create-footer">
                    <div><label for="notes">Statement note <span class="text-muted">(optional; visible to merchant)</span></label><textarea id="notes" name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes') }}</textarea></div>
                    <button class="btn admin-btn-primary" type="submit" @disabled(!($filters['merchant'] ?? null))><i class="fas fa-file-invoice-dollar mr-2"></i>Generate draft statement</button>
                </div>
            </div>
        </form>
        <div class="admin-pagination mb-4">{{ $orders->links('pagination::bootstrap-4') }}</div>

        <div class="card admin-card dashboard-data-card">
            <div class="admin-card-header"><div><h2>Generated statements</h2><p>Drafts can be reviewed or deleted. Published statements are permanent.</p></div></div>
            <div class="table-responsive"><table class="table dashboard-data-table"><thead><tr><th>Statement</th><th>Merchant</th><th>Period</th><th>Orders</th><th>Total</th><th>Commission</th><th>Merchant net</th><th>Status</th><th></th></tr></thead><tbody>
                @forelse($statements as $statement)<tr><td><strong>{{ $statement->reference }}</strong><small>{{ ($statement->issued_at ?: $statement->created_at)->format('M d, Y · g:i A') }}</small></td><td>{{ $statement->partner?->restaurant_name ?? 'Unavailable' }}</td><td>{{ $statement->period_start?->format('M d, Y') }} – {{ $statement->period_end?->format('M d, Y') }}</td><td>{{ $statement->order_count }}</td><td class="dashboard-money">₱{{ number_format($statement->total_amount, 2) }}</td><td class="dashboard-money">₱{{ number_format($statement->commission_amount, 2) }}</td><td class="dashboard-money"><strong>₱{{ number_format($statement->merchant_net_amount, 2) }}</strong></td><td><span class="dashboard-status-pill {{ $statement->isPublished() ? 'is-success' : 'is-warning' }}">{{ $statement->isPublished() ? 'Published' : 'Draft' }}</span></td><td><a class="btn btn-sm admin-btn-secondary" href="{{ route('dashboard.statements.show', $statement) }}">View</a></td></tr>
                @empty<tr><td colspan="9" class="dashboard-table-empty">No statements have been issued yet.</td></tr>@endforelse
            </tbody></table></div>
            <div class="card-footer">{{ $statements->links('pagination::bootstrap-4') }}</div>
        </div>
    </div></section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const dateRange = $('#statement-date-range');
    const initialDates = dateRange.val().split(' - ');
    const pickerOptions = {
        autoUpdateInput: false,
        opens: 'left',
        locale: { format: 'MM/DD/YYYY', cancelLabel: 'Clear' }
    };
    if (initialDates.length === 2) {
        pickerOptions.startDate = moment(initialDates[0], 'MM/DD/YYYY');
        pickerOptions.endDate = moment(initialDates[1], 'MM/DD/YYYY');
    }
    dateRange.daterangepicker(pickerOptions);
    dateRange.on('apply.daterangepicker', function (event, picker) {
        $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
    });
    dateRange.on('cancel.daterangepicker', function () { $(this).val(''); });

    const selectAll = document.getElementById('select-all-orders');
    if (selectAll) selectAll.addEventListener('change', function () {
        document.querySelectorAll('.statement-order-checkbox').forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
    });
    const form = document.getElementById('statement-create-form');
    form.addEventListener('submit', function (event) {
        const count = document.querySelectorAll('.statement-order-checkbox:checked').length;
        if (!count || !window.confirm('Generate a draft statement containing ' + count + ' selected order(s)?')) event.preventDefault();
    });
});
</script>
@endsection
