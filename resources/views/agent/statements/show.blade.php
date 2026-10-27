@extends('agent.layouts.app')

@section('title', $statement->reference)

@section('content')
    <div class="agent-page-head"><div><p class="agent-eyebrow">Statement of account</p><h1>{{ $statement->reference }}</h1><p>Issued {{ $statement->issued_at->format('F d, Y \a\t g:i A') }} · permanently locked</p></div><a class="agent-button" href="{{ route('agent.statements.index') }}">← All statements</a></div>

    <section class="agent-report-totals" aria-label="Statement totals">
        <article class="agent-report-total"><span>Transactions</span><strong>{{ number_format($statement->transaction_count) }}</strong></article>
        <article class="agent-report-total"><span>Pahatud commission</span><strong>₱{{ number_format($statement->pahatud_commission_amount, 2) }}</strong></article>
        <article class="agent-report-total"><span>Your commission</span><strong>₱{{ number_format($statement->agent_commission_amount, 2) }}</strong></article>
    </section>

    <section class="agent-card">
        <div class="agent-card-header"><div><h2>{{ $statement->period_start?->format('M d, Y') }} – {{ $statement->period_end?->format('M d, Y') }}</h2><p>These amounts are snapshots of the commission ledger when this statement was issued.</p></div></div>
        <div class="agent-table-wrap"><table class="agent-table"><thead><tr><th>Qualified</th><th>Restaurant / order</th><th>Order amount</th><th>Pahatud commission</th><th>Agent share</th><th>Agent commission</th><th>Status at issue</th></tr></thead><tbody>
            @foreach($statement->items as $item)<tr><td><strong class="agent-date">{{ $item->qualified_at->format('M d, Y') }}</strong><span class="agent-table-secondary">{{ $item->qualified_at->format('g:i A') }}</span></td><td class="agent-table-primary">{{ $item->restaurant_name ?? 'Unavailable' }}<span class="agent-order-number">Commission #{{ $item->agent_commission_id }} · Order #{{ $item->order_number ?? $item->order_id }}</span></td><td>₱{{ number_format($item->order_amount, 2) }}</td><td><div class="agent-rate-stack"><strong>₱{{ number_format($item->pahatud_commission_amount, 2) }}</strong><span>{{ number_format($item->pahatud_commission_percentage, 2) }}%</span></div></td><td>{{ number_format($item->agent_commission_percentage, 2) }}%</td><td><div class="agent-commission-result is-earned"><strong>₱{{ number_format($item->agent_commission_amount, 2) }}</strong></div></td><td><span class="agent-badge agent-badge-{{ $item->commission_status }}">{{ $item->commission_status }}</span></td></tr>@endforeach
        </tbody></table></div>
        @if($statement->notes)<div class="agent-tier-note"><strong>Statement note:</strong> {{ $statement->notes }}</div>@endif
    </section>
@endsection
