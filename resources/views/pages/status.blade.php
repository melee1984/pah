@extends('templates.inside')

@section('title', 'System Status')
@section('description', 'Check the current operating status of PahatudFood and review active and recently resolved service issues.')

@section('content')
    @php
        $statusContent = match ($overallStatus) {
            'maintenance' => [
                'label' => 'Under Maintenance',
                'message' => 'Planned or emergency maintenance is currently in progress.',
                'icon' => 'icofont-tools-alt-2',
            ],
            'issues' => [
                'label' => 'Experiencing Issues',
                'message' => 'Some PahatudFood services may be slower or unavailable.',
                'icon' => 'icofont-warning-alt',
            ],
            default => [
                'label' => 'Operational',
                'message' => 'All PahatudFood services are operating normally.',
                'icon' => 'icofont-check-circled',
            ],
        };
        $stageOrder = [
            \App\SystemIncident::STATUS_INVESTIGATING,
            \App\SystemIncident::STATUS_IN_PROGRESS,
            \App\SystemIncident::STATUS_RESOLVED,
        ];
    @endphp

    <section class="status-page-hero">
        <div class="container">
            <nav class="public-breadcrumb" aria-label="Breadcrumb">
                <a href="{{ route('home') }}">Home</a>
                <i class="icofont-rounded-right" aria-hidden="true"></i>
                <span aria-current="page">System Status</span>
            </nav>
            <div class="status-hero-grid">
                <div>
                    <span class="public-page-kicker">Live service updates</span>
                    <h1>System Status</h1>
                    <p>Follow service interruptions and maintenance updates for PahatudFood.</p>
                </div>
                <div class="status-overview status-overview-{{ $overallStatus }}" role="status">
                    <span class="status-overview-icon"><i class="{{ $statusContent['icon'] }}" aria-hidden="true"></i></span>
                    <div><small>Current status</small><strong>{{ $statusContent['label'] }}</strong><p>{{ $statusContent['message'] }}</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="status-page-content">
        <div class="container status-page-layout">
            <div class="status-main-column">
                <div class="status-section-heading">
                    <div><span class="public-section-kicker">Right now</span><h2>Active issues</h2></div>
                    <span>{{ $activeIncidents->count() }} {{ Str::plural('update', $activeIncidents->count()) }}</span>
                </div>

                @forelse ($activeIncidents as $incident)
                    @php $currentStage = array_search($incident->status, $stageOrder, true); @endphp
                    <article class="status-incident-card">
                        <div class="status-incident-header">
                            <div><span class="status-type status-type-{{ $incident->type }}">{{ $incident->type_label }}</span><h3>{{ $incident->title }}</h3></div>
                            <span class="status-stage-badge status-stage-{{ $incident->status }}">{{ $incident->status_label }}</span>
                        </div>
                        <p>{{ $incident->description }}</p>
                        <div class="status-stage-track" aria-label="Issue progress: {{ $incident->status_label }}">
                            @foreach ($stageOrder as $index => $stage)
                                <div class="status-stage-step {{ $index < $currentStage ? 'is-complete' : '' }} {{ $index === $currentStage ? 'is-current' : '' }}">
                                    <span>@if ($index < $currentStage)<i class="icofont-check" aria-hidden="true"></i>@else{{ $index + 1 }}@endif</span>
                                    <strong>{{ match ($stage) { 'in_progress' => 'Fix in Progress', 'resolved' => 'Resolved', default => 'Investigating' } }}</strong>
                                </div>
                            @endforeach
                        </div>
                        <footer><i class="icofont-clock-time" aria-hidden="true"></i> Last updated {{ $incident->updated_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</footer>
                    </article>
                @empty
                    <div class="status-empty-state">
                        <span><i class="icofont-check-circled" aria-hidden="true"></i></span>
                        <h3>No active issues</h3>
                        <p>Everything is running normally. We’ll post updates here if that changes.</p>
                    </div>
                @endforelse
            </div>

            <aside class="status-history-column">
                <div class="status-section-heading"><div><span class="public-section-kicker">Previous updates</span><h2>Resolved history</h2></div></div>
                <div class="status-history-list">
                    @forelse ($resolvedIncidents as $incident)
                        <article>
                            <span class="status-history-check"><i class="icofont-check" aria-hidden="true"></i></span>
                            <div><span class="status-type status-type-{{ $incident->type }}">{{ $incident->type_label }}</span><h3>{{ $incident->title }}</h3><p>{{ $incident->description }}</p><time datetime="{{ $incident->resolved_at?->toIso8601String() }}">Resolved {{ ($incident->resolved_at ?? $incident->updated_at)->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</time></div>
                        </article>
                    @empty
                        <div class="status-history-empty">No resolved issues have been recorded yet.</div>
                    @endforelse
                </div>
                @if ($resolvedIncidents->hasPages())
                    <div class="status-pagination">{{ $resolvedIncidents->links('pagination::bootstrap-4') }}</div>
                @endif
            </aside>
        </div>
    </section>
@endsection
