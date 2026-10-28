@extends('dashboard.template.main2')

@section('content')
@php
    $overallLabel = match ($overallStatus) {
        'maintenance' => 'Under Maintenance',
        'issues' => 'Experiencing Issues',
        default => 'Operational',
    };
@endphp
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Public communications</span><h1>System Status</h1><p>Create incidents, move them through each response stage, and keep customers informed.</p></div><a class="btn admin-btn-secondary" href="{{ route('system-status.index') }}" target="_blank" rel="noopener"><i class="fas fa-external-link-alt mr-2"></i>View public page</a></div></div>
    </section>

    <section class="content"><div class="container-fluid">
        @if (session('success'))<div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div>@endif

        <div class="system-status-admin-summary is-{{ $overallStatus }}">
            <span><i class="fas {{ $overallStatus === 'operational' ? 'fa-check' : ($overallStatus === 'maintenance' ? 'fa-tools' : 'fa-exclamation') }}"></i></span>
            <div><small>Public status now</small><strong>{{ $overallLabel }}</strong><p>This is calculated automatically from unresolved issues.</p></div>
        </div>

        <div class="card admin-card mb-4">
            <div class="admin-card-header"><div><h2>Create an issue</h2><p>Choose maintenance when the service is intentionally unavailable.</p></div></div>
            <form method="POST" action="{{ route('dashboard.system-status.store') }}">
                @csrf
                <div class="card-body system-incident-form-grid">
                    <div class="form-group system-incident-title"><label for="incident_title">Title</label><input class="form-control" id="incident_title" name="title" value="{{ old('title') }}" maxlength="160" placeholder="Checkout is temporarily unavailable" required></div>
                    <div class="form-group"><label for="incident_type">Type</label><select class="form-control" id="incident_type" name="type" required><option value="issue" @selected(old('type') === 'issue')>Service issue</option><option value="maintenance" @selected(old('type') === 'maintenance')>Maintenance</option></select></div>
                    <div class="form-group"><label for="incident_status">Stage</label><select class="form-control" id="incident_status" name="status" required><option value="investigating" @selected(old('status') === 'investigating')>Investigating</option><option value="in_progress" @selected(old('status') === 'in_progress')>Fix in Progress</option><option value="resolved" @selected(old('status') === 'resolved')>Resolved</option></select></div>
                    <div class="form-group system-incident-description"><label for="incident_description">Short description</label><textarea class="form-control" id="incident_description" name="description" rows="3" maxlength="1000" placeholder="Tell customers what is affected and what they can expect." required>{{ old('description') }}</textarea></div>
                    <div class="system-incident-submit"><button class="btn admin-btn-primary" type="submit"><i class="fas fa-plus mr-2"></i>Create issue</button></div>
                </div>
            </form>
        </div>

        <div class="card admin-card">
            <div class="admin-card-header"><div><h2>Issue management</h2><p>{{ number_format($incidents->total()) }} {{ Str::plural('issue', $incidents->total()) }} recorded</p></div><span class="dashboard-soft-badge"><i class="fas fa-clock mr-1"></i>Philippine time</span></div>
            @forelse ($incidents as $incident)
                <form class="system-incident-admin-row" method="POST" action="{{ route('dashboard.system-status.update', $incident) }}">
                    @csrf
                    @method('PUT')
                    <div class="system-incident-admin-meta"><span class="dashboard-status-pill {{ $incident->status === 'resolved' ? 'is-success' : ($incident->type === 'maintenance' ? 'is-warning' : 'is-danger') }}">{{ $incident->status_label }}</span><small>Updated {{ $incident->updated_at->timezone('Asia/Manila')->format('M j, Y · g:i A') }} PHT</small></div>
                    <div class="system-incident-admin-fields">
                        <div class="form-group system-incident-title"><label for="title_{{ $incident->id }}">Title</label><input class="form-control" id="title_{{ $incident->id }}" name="title" value="{{ $incident->title }}" maxlength="160" required></div>
                        <div class="form-group"><label for="type_{{ $incident->id }}">Type</label><select class="form-control" id="type_{{ $incident->id }}" name="type" required><option value="issue" @selected($incident->type === 'issue')>Service issue</option><option value="maintenance" @selected($incident->type === 'maintenance')>Maintenance</option></select></div>
                        <div class="form-group"><label for="status_{{ $incident->id }}">Stage</label><select class="form-control" id="status_{{ $incident->id }}" name="status" required><option value="investigating" @selected($incident->status === 'investigating')>Investigating</option><option value="in_progress" @selected($incident->status === 'in_progress')>Fix in Progress</option><option value="resolved" @selected($incident->status === 'resolved')>Resolved</option></select></div>
                        <div class="form-group system-incident-description"><label for="description_{{ $incident->id }}">Short description</label><textarea class="form-control" id="description_{{ $incident->id }}" name="description" rows="2" maxlength="1000" required>{{ $incident->description }}</textarea></div>
                    </div>
                    <div class="system-incident-admin-action"><button class="btn admin-btn-secondary" type="submit"><i class="fas fa-save mr-2"></i>Save changes</button></div>
                </form>
            @empty
                <div class="admin-empty-state"><span><i class="fas fa-heartbeat"></i></span><h3>No system issues</h3><p>Create the first issue when customers need a service update.</p></div>
            @endforelse
            @if ($incidents->hasPages())<div class="card-footer">{{ $incidents->links('pagination::bootstrap-4') }}</div>@endif
        </div>
    </div></section>
</div>
@endsection
