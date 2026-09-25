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
            <span>Approved restaurants</span>
            <strong>{{ number_format($metrics['approved_restaurants']) }}</strong>
        </article>
        <article class="agent-metric">
            <span class="agent-metric-icon">%</span>
            <span>Current commission share</span>
            <strong>{{ number_format($metrics['commission_percentage'], 2) }}%</strong>
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

    <section class="agent-tier-panel" aria-labelledby="commission-tier-title">
        <div class="agent-tier-panel-head">
            <div><p class="agent-eyebrow">Automatic commission tiers</p><h2 id="commission-tier-title">How your approved restaurant count sets your rate</h2><p>Your current rate is selected automatically whenever a future order qualifies for commission.</p></div>
            <div class="agent-tier-progress">
                <strong>{{ number_format($metrics['approved_restaurants']) }} approved {{ Str::plural('restaurant', $metrics['approved_restaurants']) }}</strong><br>
                @if ($metrics['next_commission_tier'])
                    {{ number_format($metrics['next_commission_tier']['minimum_restaurants'] - $metrics['approved_restaurants']) }} more to unlock {{ number_format($metrics['next_commission_tier']['percentage'], 2) }}%.
                @else
                    Highest tier reached. Future qualifying orders use {{ number_format(config('agent.commission_tiers.50'), 2) }}%.
                @endif
            </div>
        </div>
        @include('agent.partials.commission-tier-guide', ['approvedRestaurantCount' => $metrics['approved_restaurants']])
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
                @include('agent.partials.commission-table', ['commissions' => $recentCommissions])
            </div>
        @endif
    </section>

    <section class="agent-guide" aria-labelledby="agent-guide-title">
        <div class="agent-guide-heading"><div><p class="agent-eyebrow">Agent guide</p><h2 id="agent-guide-title">How to get started and earn</h2><p>Use this checklist when introducing a restaurant to Pahatud.</p></div><a class="agent-button agent-button-primary" href="{{ route('agent.restaurants.create') }}">Enroll a restaurant</a></div>
        <div class="agent-guide-steps">
            <article><span>01</span><h3>Register the restaurant</h3><p>Open <a href="{{ route('agent.restaurants.create') }}">Enroll</a> and enter the restaurant, contact, address, and payout account details. The restaurant contact receives an invitation to set up their account.</p></article>
            <article><span>02</span><h3>Provide the documents</h3><p>Upload a valid government-issued ID and the DTI, SEC, or CDA business registration certificate. An authorization document is also required when the enrollee is not the owner. Missing files can be added later.</p></article>
            <article><span>03</span><h3>Follow the review</h3><p>Check <a href="{{ route('agent.restaurants.index') }}">Restaurants</a> for each application. Admins verify documents and may request replacements with remarks. The restaurant and you receive status updates. All required documents must be approved before the restaurant application can be approved.</p></article>
            <article><span>04</span><h3>Track your earnings</h3><p>Once an approved, linked restaurant completes a qualifying delivered order, your commission is recorded automatically. Use <a href="{{ route('agent.reports.index') }}">Reports</a> to see each order, calculation, and commission status.</p></article>
        </div>

        @php
            $exampleOrder = 1000;
            $examplePahatudRate = (float) config('agent.pahatud_commission_percentage');
            $exampleAgentRate = (float) $metrics['commission_percentage'];
            $exampleEarnings = $exampleOrder * $examplePahatudRate / 100 * $exampleAgentRate / 100;
        @endphp
        <div class="agent-guide-bottom">
            <div class="agent-guide-example"><h3>How your commission is calculated</h3><p>Your current share is <strong>{{ number_format($exampleAgentRate, 2) }}% of Pahatud’s commission</strong> from qualifying orders, not of the full order value.</p><div class="agent-guide-equation"><span>₱{{ number_format($exampleOrder, 2) }} eligible subtotal</span><b>× {{ number_format($examplePahatudRate, 2) }}% Pahatud rate</b><b>× {{ number_format($exampleAgentRate, 2) }}% tier share</b><strong>= ₱{{ number_format($exampleEarnings, 2) }}</strong></div><small>Example only. Delivery fees are not included in the commission base. The restaurant’s Pahatud rate and your tier at the time the delivered order qualifies are saved with that commission entry.</small></div>
            <div class="agent-guide-notes"><h3>Good to know</h3><ul><li>Only approved restaurants count toward your tier: {{ number_format(config('agent.commission_tiers.0'), 0) }}% below 35, {{ number_format(config('agent.commission_tiers.35'), 0) }}% from 35–49, and {{ number_format(config('agent.commission_tiers.50'), 0) }}% from 50 onward.</li><li>When you reach a tier, its rate applies to future qualifying orders from all restaurants assigned to you. Existing commission entries do not change.</li><li>Cancelled or reversed orders do not count toward earned commission.</li><li>Restaurants can update details and replace documents from their account. Changes return an approved application to review.</li><li>Reports show pending, approved, paid, and reversed entries. Pahatud operations coordinates payouts.</li></ul></div>
        </div>
        <a class="agent-guide-more" href="{{ route('agent.help') }}">More questions? Visit Help &amp; FAQ <span aria-hidden="true">→</span></a>
    </section>
@endsection
