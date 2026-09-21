@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header">
        <div class="container-fluid">
            <div class="admin-page-heading">
                <div>
                    <span class="admin-eyebrow">Rider management / Rider #{{ $rider->id }}</span>
                    <h1>{{ $rider->name ?: 'Unnamed rider' }}</h1>
                    <p>Account, application, and vehicle information.</p>
                </div>
                <a class="btn admin-btn-secondary" href="{{ route('dashboard.rider') }}"><i class="fas fa-arrow-left mr-2"></i>All riders</a>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="container-fluid">
            <div class="admin-stat-grid">
                <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-user-check"></i></span><div><small>Approval</small><strong>{{ $rider->active ? 'Approved' : 'Pending' }}</strong></div></article>
                <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-wallet"></i></span><div><small>Credit balance</small><strong>₱{{ number_format((float) ($rider->wallet?->credit_amount ?? 0), 2) }}</strong></div></article>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card admin-card mb-4">
                        <div class="admin-card-header"><div><h2>Rider account</h2><p>Current account details</p></div></div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $rider->name ?: 'Not provided' }}</dd>
                                <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8">{{ $rider->mobile ?: 'Not provided' }}</dd>
                                <dt class="col-sm-4">License number</dt><dd class="col-sm-8">{{ $rider->license_no ?: 'Not provided' }}</dd>
                                <dt class="col-sm-4">Joined</dt><dd class="col-sm-8">{{ $rider->date_join?->format('M d, Y') ?? $rider->created_at?->format('M d, Y') ?? '—' }}</dd>
                                <dt class="col-sm-4">Approved</dt><dd class="col-sm-8">{{ $rider->approved_at?->format('M d, Y · g:i A') ?? 'Not yet approved' }}</dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card admin-card mb-4">
                        <div class="admin-card-header"><div><h2>Personal information</h2><p>From the rider application</p></div></div>
                        <div class="card-body">
                            @if ($application)
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Email</dt><dd class="col-sm-8">{{ $application->email ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Birth date</dt><dd class="col-sm-8">{{ $application->birth_date?->format('M d, Y') ?? 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Home address</dt><dd class="col-sm-8">{{ $application->home_address ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Application</dt><dd class="col-sm-8">{{ Str::headline($application->status) }}</dd>
                                    <dt class="col-sm-4">Submitted</dt><dd class="col-sm-8">{{ $application->submitted_at?->format('M d, Y · g:i A') ?? 'Not submitted' }}</dd>
                                </dl>
                            @else
                                <p class="mb-0 text-muted">No matching rider application was found.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if ($application)
                <div class="row">
                    <div class="col-lg-6">
                        <div class="card admin-card mb-4">
                            <div class="admin-card-header"><div><h2>Vehicle</h2><p>Registered delivery vehicle</p></div></div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Type</dt><dd class="col-sm-8">{{ $application->vehicle_type ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Make and model</dt><dd class="col-sm-8">{{ $application->vehicle_make_model ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Plate number</dt><dd class="col-sm-8">{{ $application->vehicle_plate_number ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Color</dt><dd class="col-sm-8">{{ $application->vehicle_color ?: 'Not provided' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card admin-card mb-4">
                            <div class="admin-card-header"><div><h2>Emergency contact</h2><p>Contact provided during application</p></div></div>
                            <div class="card-body">
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Name</dt><dd class="col-sm-8">{{ $application->emergency_contact_name ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Relationship</dt><dd class="col-sm-8">{{ $application->emergency_contact_relationship ?: 'Not provided' }}</dd>
                                    <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8">{{ $application->emergency_contact_mobile ?: 'Not provided' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card admin-card mb-4">
                    <div class="admin-card-header"><div><h2>Documents</h2><p>Files submitted with the application</p></div></div>
                    @if ($application->documents->isEmpty())
                        <div class="card-body"><p class="mb-0 text-muted">No documents uploaded.</p></div>
                    @else
                        <div class="card-body">
                            @foreach ($application->documents as $document)
                                <a class="dashboard-inline-link d-block mb-2" href="{{ route('dashboard.rider-applications.documents.show', [$application, $document]) }}" target="_blank" rel="noopener"><i class="fas fa-file-alt mr-1"></i>{{ Str::headline($document->type) }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
</div>
@endsection
