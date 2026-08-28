@extends('agent.layouts.app')

@section('title', 'Commission Report')

@section('content')
    <div class="agent-page-head">
        <div>
            <p class="agent-eyebrow">Commission ledger</p>
            <h1>Reports</h1>
            <p>Review qualifying orders and commission transactions by date and status.</p>
        </div>
    </div>

    <section class="agent-card">
        <form class="agent-filter" method="GET" action="{{ route('agent.reports.index') }}">
            <div class="agent-field"><label for="from">From</label><input class="agent-input" id="from" name="from" type="date" value="{{ request('from', $from->toDateString()) }}"></div>
            <div class="agent-field"><label for="to">To</label><input class="agent-input" id="to" name="to" type="date" value="{{ request('to', $to->toDateString()) }}"></div>
            <div class="agent-field"><label for="status">Commission status</label><select class="agent-input" id="status" name="status"><option value="">All statuses</option>@foreach (['pending', 'approved', 'paid', 'reversed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <button class="agent-button agent-button-primary" type="submit">Apply filter</button>
        </form>
    </section>

    <section class="agent-report-totals" aria-label="Report totals">
        <article class="agent-report-total"><span>Total orders</span><strong>{{ number_format($totals['orders']) }}</strong></article>
        <article class="agent-report-total"><span>Total order value</span><strong>₱{{ number_format($totals['order_value'], 2) }}</strong></article>
        <article class="agent-report-total"><span>Total agent commission</span><strong>₱{{ number_format($totals['commission'], 2) }}</strong></article>
    </section>

    <section class="agent-card">
        <div class="agent-card-header"><div><h2>{{ $from->format('M d, Y') }} – {{ $to->format('M d, Y') }}</h2><p>Reversed commissions remain visible for a complete audit trail and are excluded from the commission total.</p></div></div>
        @if ($commissions->isEmpty())
            <div class="agent-empty"><strong>No transactions in this period</strong>Try a wider date range or a different commission status.</div>
        @else
            <div class="agent-table-wrap">
                <table class="agent-table">
                    <thead><tr><th>Restaurant</th><th>Orders</th><th>Order amount</th><th>Agent share</th><th>Agent commission</th><th>Date</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($commissions as $commission)
                        <tr>
                            <td class="agent-table-primary">{{ $commission->restaurant?->restaurant_name ?? 'Restaurant unavailable' }}<span class="agent-table-secondary">Order #{{ $commission->order?->order_no ?? $commission->order_id }}</span></td>
                            <td>1</td>
                            <td class="agent-money">₱{{ number_format($commission->order_amount, 2) }}</td>
                            <td>{{ number_format($commission->commission_percentage, 2) }}%</td>
                            <td class="agent-money {{ $commission->status !== 'reversed' ? 'agent-money-positive' : '' }}">₱{{ number_format($commission->commission_amount, 2) }}</td>
                            <td>{{ $commission->qualified_at->format('M d, Y') }}<span class="agent-table-secondary">{{ $commission->qualified_at->format('g:i A') }}</span></td>
                            <td><span class="agent-badge agent-badge-{{ $commission->status }}">{{ $commission->status }}</span>@if($commission->reversal_reason)<span class="agent-table-secondary">{{ $commission->reversal_reason }}</span>@endif</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="agent-pagination">{{ $commissions->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
@endsection
