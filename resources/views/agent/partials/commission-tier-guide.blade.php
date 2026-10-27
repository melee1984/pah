@php
    $tierApprovedCount = isset($approvedRestaurantCount) ? (int) $approvedRestaurantCount : null;
    $tierExampleOrder = (float) ($exampleOrderAmount ?? 1000);
    $tierPahatudRate = (float) config('agent.pahatud_commission_percentage');
    $tierRows = [
        ['minimum' => 0, 'maximum' => 34, 'percentage' => (float) config('agent.commission_tiers.0', 20)],
        ['minimum' => 35, 'maximum' => 49, 'percentage' => (float) config('agent.commission_tiers.35', 25)],
        ['minimum' => 50, 'maximum' => null, 'percentage' => (float) config('agent.commission_tiers.50', 30)],
    ];
@endphp

<div class="agent-tier-grid" aria-label="Agent commission tiers">
    @foreach ($tierRows as $tierRow)
        @php
            $rangeLabel = $tierRow['maximum'] === null
                ? number_format($tierRow['minimum']).' or more'
                : number_format($tierRow['minimum']).'–'.number_format($tierRow['maximum']);
            $isCurrentTier = $tierApprovedCount !== null
                && $tierApprovedCount >= $tierRow['minimum']
                && ($tierRow['maximum'] === null || $tierApprovedCount <= $tierRow['maximum']);
            $pahatudCommission = $tierExampleOrder * $tierPahatudRate / 100;
            $agentCommission = $pahatudCommission * $tierRow['percentage'] / 100;
        @endphp
        <article class="agent-tier-card {{ $isCurrentTier ? 'is-current' : '' }}">
            @if ($isCurrentTier)<span class="agent-tier-current">Your current tier</span>@endif
            <small>Approved restaurants</small>
            <strong>{{ $rangeLabel }}</strong>
            <div><b>{{ number_format($tierRow['percentage'], 2) }}%</b><span>of Pahatud’s commission</span></div>
            <p>On an example ₱{{ number_format($tierExampleOrder, 2) }} eligible subtotal at a {{ number_format($tierPahatudRate, 2) }}% Pahatud rate, the agent earns <strong>₱{{ number_format($agentCommission, 2) }}</strong>.</p>
        </article>
    @endforeach
</div>

<div class="agent-tier-rules">
    <article><span>1</span><div><strong>Approval determines the count</strong><p>A restaurant counts only while its application status is Approved. Pending, under-review, declined, or expired-document applications do not count.</p></div></article>
    <article><span>2</span><div><strong>The rate is checked when an order qualifies</strong><p>When an assigned restaurant’s eligible order is delivered, the system checks the agent’s approved-restaurant total and selects the active tier.</p></div></article>
    <article><span>3</span><div><strong>The tier covers the whole network</strong><p>After a threshold is reached, that rate applies to future qualifying orders from every restaurant assigned to the agent, including restaurants enrolled earlier.</p></div></article>
    <article><span>4</span><div><strong>Recorded commissions stay unchanged</strong><p>Each commission entry saves the rate used for that order. Moving to a higher or lower tier never recalculates an existing entry.</p></div></article>
</div>
