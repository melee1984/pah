@extends('agent.layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="agent-page-head">
        <div>
            <p class="agent-eyebrow">Performance overview</p>
            <h1>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ Str::before($agent->name, ' ') }}.</h1>
            <p>Here’s what your restaurant network is doing today.</p>
        </div>
        <a class="agent-button agent-button-primary" href="{{ route('agent.restaurants.create') }}"><span>＋</span> Enroll restaurant</a>
    </div>

    <section class="agent-metrics" aria-label="Agent performance metrics">
        <article class="agent-metric">
            <span class="agent-metric-icon">▦</span>
            <span>Enrolled restaurants</span>
            <strong>{{ number_format($metrics['restaurants']) }}</strong>
        </article>
        <article class="agent-metric">
            <span class="agent-metric-icon">◫</span>
            <span>Orders generated</span>
            <strong>{{ number_format($metrics['orders']) }}</strong>
        </article>
        <article class="agent-metric">
            <span class="agent-metric-icon">₱</span>
            <span>Completed order value</span>
            <strong>₱{{ number_format($metrics['sales'], 2) }}</strong>
        </article>
        <article class="agent-metric">
            <span class="agent-metric-icon">↗</span>
            <span>Total commission</span>
            <strong>₱{{ number_format($metrics['commission'], 2) }}</strong>
        </article>
        <article class="agent-metric agent-metric-highlight">
            <span class="agent-metric-icon">★</span>
            <span>{{ now()->format('F') }} commission</span>
            <strong>₱{{ number_format($metrics['month_commission'], 2) }}</strong>
        </article>
    </section>

    <section class="agent-card">
        <div class="agent-card-header">
            <div><h2>Recent commission activity</h2><p>Latest qualifying and reversed order commissions.</p></div>
            <a class="agent-text-link" href="{{ route('agent.reports.index') }}">View full report →</a>
        </div>
        @if ($recentCommissions->isEmpty())
            <div class="agent-empty"><strong>No commission activity yet</strong>Completed orders from your enrolled restaurants will appear here.</div>
        @else
            <div class="agent-table-wrap">
                <table class="agent-table">
                    <thead><tr><th>Restaurant</th><th>Order</th><th>Order value</th><th>Agent share</th><th>Commission</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    @foreach ($recentCommissions as $commission)
                        <tr>
                            <td class="agent-table-primary">{{ $commission->restaurant?->restaurant_name ?? 'Restaurant unavailable' }}</td>
                            <td>#{{ $commission->order?->order_no ?? $commission->order_id }}</td>
                            <td class="agent-money">₱{{ number_format($commission->order_amount, 2) }}</td>
                            <td>{{ number_format($commission->commission_percentage, 2) }}%</td>
                            <td class="agent-money agent-money-positive">₱{{ number_format($commission->commission_amount, 2) }}</td>
                            <td><span class="agent-badge agent-badge-{{ $commission->status }}">{{ $commission->status }}</span></td>
                            <td>{{ $commission->qualified_at->format('M d, Y') }}<span class="agent-table-secondary">{{ $commission->qualified_at->format('g:i A') }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
