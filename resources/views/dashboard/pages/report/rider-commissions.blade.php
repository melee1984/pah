@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Delivery reporting</span>
                    <h1>Rider Commission Report</h1>
                    <p>Review the Pahatud commission deducted from completed rider deliveries.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            @if ($errors->any())
                <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>
            @endif

            <div class="card admin-card rider-report-filter-card">
                <form class="dashboard-filter-bar rider-commission-filters" method="GET" action="{{ route('dashboard.report.riders') }}">
                    <div>
                        <label for="rider_id">Rider</label>
                        <select class="form-control" id="rider_id" name="rider_id">
                            <option value="">All riders</option>
                            @foreach ($riders as $rider)
                                <option value="{{ $rider->id }}" @selected($selectedRiderId === $rider->id)>{{ $rider->name ?: 'Rider #'.$rider->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="from">From</label>
                        <input class="form-control" id="from" name="from" type="date" value="{{ $from->toDateString() }}" required>
                    </div>
                    <div>
                        <label for="to">To</label>
                        <input class="form-control" id="to" name="to" type="date" value="{{ $to->toDateString() }}" required>
                    </div>
                    <div class="dashboard-filter-actions">
                        <button class="btn admin-btn-primary" type="submit"><i class="fas fa-filter mr-2"></i>Apply filters</button>
                        <a class="btn admin-btn-secondary" href="{{ route('dashboard.report.riders') }}">Reset</a>
                    </div>
                </form>
            </div>

            <div class="admin-stat-grid rider-report-stats">
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-coins"></i></span><div><small>Total commission</small><strong>₱{{ number_format($metrics['total_centavos'] / 100, 2) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-motorcycle"></i></span><div><small>Completed deliveries</small><strong>{{ number_format($metrics['count']) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-percentage"></i></span><div><small>Average commission</small><strong>₱{{ number_format($metrics['average_centavos'] / 100, 2) }}</strong></div></article>
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-wallet"></i></span><div><small>{{ $selectedRider ? 'Current wallet' : 'Report period' }}</small><strong>{{ $selectedRider ? '₱'.number_format($metrics['wallet_balance'], 2) : $from->format('M d').' – '.$to->format('M d, Y') }}</strong></div></article>
            </div>

            <div class="card admin-card">
                <div class="admin-card-header">
                    <div>
                        <h2>{{ $selectedRider?->name ?? 'All riders' }}</h2>
                        <p>{{ number_format($commissions->total()) }} commission {{ Str::plural('entry', $commissions->total()) }} from {{ $from->format('M d, Y') }} to {{ $to->format('M d, Y') }}</p>
                    </div>
                </div>

                @if ($commissions->isEmpty())
                    <div class="admin-empty-state"><span><i class="fas fa-receipt"></i></span><h3>No commissions found</h3><p>Try another rider or date range.</p></div>
                @else
                    <div class="table-responsive">
                        <table class="table admin-table rider-commission-table">
                            <thead><tr><th>Date/time</th><th>Rider</th><th>Booking</th><th>Delivery fee</th><th>Rate</th><th>Commission</th><th>Wallet after</th></tr></thead>
                            <tbody>
                            @foreach ($commissions as $commission)
                                <tr>
                                    <td><strong>{{ \Carbon\Carbon::parse($commission->occurred_at)->format('M d, Y') }}</strong><small>{{ \Carbon\Carbon::parse($commission->occurred_at)->format('g:i A') }}</small></td>
                                    <td><strong>{{ $commission->rider_name ?: 'Rider #'.$commission->rider_id }}</strong><small>Rider #{{ $commission->rider_id }}</small></td>
                                    <td>
                                        <strong>{{ $commission->legacy_order_id ? 'Order #'.$commission->legacy_order_id : ($commission->legacy_booking_id ? 'Booking #'.$commission->legacy_booking_id : 'Delivery') }}</strong>
                                        <small>{{ $commission->delivery_reference ? Str::limit($commission->delivery_reference, 18) : Str::limit($commission->reference, 18) }}</small>
                                    </td>
                                    <td><span class="dashboard-money">{{ $commission->earnings_centavos !== null ? '₱'.number_format($commission->earnings_centavos / 100, 2) : '—' }}</span></td>
                                    <td><strong>{{ $commission->commission_percentage !== null ? number_format($commission->commission_percentage, 2).'%' : '—' }}</strong></td>
                                    <td><strong class="admin-money">₱{{ number_format(abs($commission->amount_centavos) / 100, 2) }}</strong></td>
                                    <td><span class="dashboard-money">₱{{ number_format($commission->balance_after_centavos / 100, 2) }}</span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="admin-pagination">{{ $commissions->links('pagination::bootstrap-4') }}</div>
                @endif
            </div>
        </div>
    </section>
</div>
@endsection
