@extends('merchant.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper"><section class="content-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Financial records</span><h1>Statements of account</h1><p>View statements issued by Pahatud. These records are read-only.</p></div></div></div></section><section class="content"><div class="container-fluid">
    <div class="card admin-card dashboard-data-card"><div class="admin-card-header"><div><h2>Issued statements</h2><p>{{ number_format($statements->total()) }} {{ Str::plural('statement', $statements->total()) }} available</p></div><span class="dashboard-soft-badge"><i class="fas fa-lock mr-1"></i>Read only</span></div>
    <div class="table-responsive"><table class="table dashboard-data-table"><thead><tr><th>Statement</th><th>Issued</th><th>Period</th><th>Orders</th><th>Order total</th><th>Pahatud commission</th><th>Merchant net</th><th>Status</th><th></th></tr></thead><tbody>
    @forelse($statements as $statement)<tr><td><strong>{{ $statement->reference }}</strong></td><td>{{ $statement->issued_at->format('M d, Y') }}<small>{{ $statement->issued_at->format('g:i A') }}</small></td><td>{{ $statement->period_start?->format('M d, Y') }} – {{ $statement->period_end?->format('M d, Y') }}</td><td>{{ $statement->order_count }}</td><td class="dashboard-money">₱{{ number_format($statement->total_amount, 2) }}</td><td class="dashboard-money">₱{{ number_format($statement->commission_amount, 2) }}</td><td class="dashboard-money"><strong>₱{{ number_format($statement->merchant_net_amount, 2) }}</strong></td><td><span class="dashboard-status-pill is-success">Issued</span></td><td><a class="btn btn-sm admin-btn-secondary" href="{{ route('merchant.dashboard.report.soa.show', $statement) }}">View</a></td></tr>
    @empty<tr><td colspan="9" class="dashboard-table-empty">No statements have been issued to your account yet.</td></tr>@endforelse
    </tbody></table></div><div class="card-footer">{{ $statements->links('pagination::bootstrap-4') }}</div></div>
</div></section></div>
@endsection
