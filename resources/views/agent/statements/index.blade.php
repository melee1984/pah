@extends('agent.layouts.app')

@section('title', 'Statements of Account')

@section('content')
    <div class="agent-page-head"><div><p class="agent-eyebrow">Commission statements</p><h1>Statements of account</h1><p>View permanent statements issued for your commission transactions.</p></div></div>

    <section class="agent-card">
        <div class="agent-card-header"><div><h2>Issued statements</h2><p>Each commission transaction appears on only one statement.</p></div></div>
        @if($statements->isEmpty())
            <div class="agent-empty"><strong>No statements yet</strong>Your generated statements will appear here.</div>
        @else
            <div class="agent-table-wrap"><table class="agent-table"><thead><tr><th>Statement</th><th>Issued</th><th>Period</th><th>Transactions</th><th>Agent commission</th><th></th></tr></thead><tbody>
                @foreach($statements as $statement)<tr><td class="agent-table-primary">{{ $statement->reference }}</td><td><strong class="agent-date">{{ $statement->issued_at->format('M d, Y') }}</strong><span class="agent-table-secondary">{{ $statement->issued_at->format('g:i A') }}</span></td><td>{{ $statement->period_start?->format('M d, Y') }} – {{ $statement->period_end?->format('M d, Y') }}</td><td>{{ number_format($statement->transaction_count) }}</td><td><div class="agent-commission-result is-earned"><strong>₱{{ number_format($statement->agent_commission_amount, 2) }}</strong></div></td><td><a class="agent-button agent-button-primary" href="{{ route('agent.statements.show', $statement) }}">View</a></td></tr>@endforeach
            </tbody></table></div>
            <div class="agent-pagination">{{ $statements->links('pagination::bootstrap-4') }}</div>
        @endif
    </section>
@endsection
