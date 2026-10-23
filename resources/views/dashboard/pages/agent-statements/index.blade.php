@extends('dashboard.template.main')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Agent billing</span><h1>Agent statements of account</h1><p>Select commission transactions for one agent and generate a permanent statement.</p></div></div></div></section>
    <section class="content"><div class="container-fluid statement-page">
        @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger"><strong>The statement was not generated.</strong><ul class="mb-0 mt-2">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="card admin-card mb-4">
            <div class="admin-card-header"><div><h2>1. Find unprocessed commissions</h2><p>Reversed transactions and commissions already assigned to an agent statement are excluded.</p></div></div>
            <form class="dashboard-filter-bar statement-filter-bar" method="GET" action="{{ route('dashboard.agent-statements.index') }}">
                <div><label for="agent_id">Agent</label><select id="agent_id" name="agent_id" class="form-control"><option value="">All agents</option>@foreach($agents as $agent)<option value="{{ $agent->id }}" @selected((string) $selectedAgentId === (string) $agent->id)>{{ $agent->name }}</option>@endforeach</select></div>
                <div><label for="from">From</label><input id="from" name="from" type="date" class="form-control" value="{{ $filters['from'] ?? '' }}"></div>
                <div><label for="to">To</label><input id="to" name="to" type="date" class="form-control" value="{{ $filters['to'] ?? '' }}"></div>
                <div><label for="search">Search</label><input id="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Order or restaurant"></div>
                <div class="dashboard-filter-actions"><a class="btn admin-btn-secondary" href="{{ route('dashboard.agent-statements.index') }}">Clear</a><button class="btn admin-btn-primary" type="submit"><i class="fas fa-search mr-1"></i>Search</button></div>
            </form>
        </div>

        <form method="POST" action="{{ route('dashboard.agent-statements.store') }}" id="agent-statement-create-form">
            @csrf
            <input type="hidden" name="agent_id" value="{{ $selectedAgentId }}">
            <div class="card admin-card dashboard-data-card mb-4">
                <div class="admin-card-header"><div><h2>2. Select transactions</h2><p>@if($selectedAgentId)Each selected commission will be permanently assigned to this statement.@else Select an agent above before choosing transactions.@endif</p></div>@if($selectedAgentId)<label class="statement-select-all"><input type="checkbox" id="select-all-commissions"> Select all on this page</label>@endif</div>
                <div class="table-responsive"><table class="table dashboard-data-table"><thead><tr><th></th><th>Qualified</th><th>Agent</th><th>Restaurant / order</th><th>Order amount</th><th>Pahatud commission</th><th>Agent share</th><th>Agent commission</th><th>Status</th></tr></thead><tbody>
                    @forelse($commissions as $commission)
                        @php
                            $canSelect = (string) $selectedAgentId === (string) $commission->agent_id;
                            $orderAmount = (float) ($commission->total_amount ?? $commission->order_amount);
                            $pahatudPercentage = (float) ($commission->pahatud_commission_percentage ?? $commission->restaurant?->percentage ?? config('agent.pahatud_commission_percentage'));
                            $pahatudAmount = (float) ($commission->pahatud_commission_amount ?? round((float) ($commission->subtotal_amount ?? $commission->order_amount) * ($pahatudPercentage / 100), 2));
                        @endphp
                        <tr>
                            <td>@if($canSelect)<input class="agent-statement-checkbox" type="checkbox" name="commission_ids[]" value="{{ $commission->id }}" @checked(in_array($commission->id, old('commission_ids', []))) aria-label="Select commission {{ $commission->id }}">@else<span class="text-muted">—</span>@endif</td>
                            <td><strong>{{ $commission->qualified_at?->format('M d, Y') }}</strong><small>{{ $commission->qualified_at?->format('g:i A') }}</small></td>
                            <td>{{ $commission->agent?->name ?? 'Unavailable' }}</td>
                            <td><strong>{{ $commission->restaurant?->restaurant_name ?? 'Unavailable' }}</strong><small>Commission #{{ $commission->id }} · Order #{{ $commission->order?->cart?->order_no ?? $commission->order_id }}</small></td>
                            <td class="dashboard-money">₱{{ number_format($orderAmount, 2) }}</td>
                            <td class="dashboard-money">₱{{ number_format($pahatudAmount, 2) }}<small>{{ number_format($pahatudPercentage, 2) }}%</small></td>
                            <td class="dashboard-money">{{ number_format($commission->commission_percentage, 2) }}%</td>
                            <td class="dashboard-money"><strong>₱{{ number_format($commission->commission_amount, 2) }}</strong></td>
                            <td><span class="dashboard-status-pill is-success">{{ ucfirst($commission->status) }}</span></td>
                        </tr>
                    @empty<tr><td colspan="9" class="dashboard-table-empty">No unprocessed agent commissions match this search.</td></tr>@endforelse
                </tbody></table></div>
                <div class="card-footer statement-create-footer"><div><label for="notes">Statement note <span class="text-muted">(optional; visible to agent after publication)</span></label><textarea id="notes" name="notes" class="form-control" rows="2" maxlength="1000">{{ old('notes') }}</textarea></div><button class="btn admin-btn-primary" type="submit" @disabled(!$selectedAgentId)><i class="fas fa-file-invoice-dollar mr-2"></i>Generate draft statement</button></div>
            </div>
        </form>
        <div class="admin-pagination mb-4">{{ $commissions->links('pagination::bootstrap-4') }}</div>

        <div class="card admin-card dashboard-data-card">
            <div class="admin-card-header"><div><h2>Generated agent statements</h2><p>Drafts remain private. Published statements are visible to the agent and permanently locked.</p></div></div>
            <div class="table-responsive"><table class="table dashboard-data-table"><thead><tr><th>Statement</th><th>Agent</th><th>Period</th><th>Transactions</th><th>Pahatud commission</th><th>Agent commission</th><th>Status</th><th></th></tr></thead><tbody>
                @forelse($statements as $statement)<tr><td><strong>{{ $statement->reference }}</strong><small>{{ ($statement->issued_at ?: $statement->created_at)->format('M d, Y · g:i A') }}</small></td><td>{{ $statement->agent?->name ?? 'Unavailable' }}</td><td>{{ $statement->period_start?->format('M d, Y') }} – {{ $statement->period_end?->format('M d, Y') }}</td><td>{{ $statement->transaction_count }}</td><td class="dashboard-money">₱{{ number_format($statement->pahatud_commission_amount, 2) }}</td><td class="dashboard-money"><strong>₱{{ number_format($statement->agent_commission_amount, 2) }}</strong></td><td><span class="dashboard-status-pill {{ $statement->isPublished() ? 'is-success' : 'is-warning' }}">{{ $statement->isPublished() ? 'Published' : 'Draft' }}</span></td><td><a class="btn btn-sm admin-btn-secondary" href="{{ route('dashboard.agent-statements.show', $statement) }}">View</a></td></tr>
                @empty<tr><td colspan="8" class="dashboard-table-empty">No agent statements have been generated yet.</td></tr>@endforelse
            </tbody></table></div><div class="card-footer">{{ $statements->links('pagination::bootstrap-4') }}</div>
        </div>
    </div></section>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectAll = document.getElementById('select-all-commissions');
    if (selectAll) selectAll.addEventListener('change', function () {
        document.querySelectorAll('.agent-statement-checkbox').forEach(function (checkbox) { checkbox.checked = selectAll.checked; });
    });
    document.getElementById('agent-statement-create-form').addEventListener('submit', function (event) {
        const count = document.querySelectorAll('.agent-statement-checkbox:checked').length;
        if (!count || !window.confirm('Generate a private draft containing ' + count + ' commission transaction(s)? The transactions will be reserved for this statement.')) event.preventDefault();
    });
});
</script>
@endsection
