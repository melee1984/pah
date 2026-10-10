@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header admin-page-header"><div class="container-fluid"><div class="admin-page-heading">
        <div><span class="admin-eyebrow">Merchant recruitment / Application #{{ $application->id }}</span><h1>{{ $application->business_name }}</h1><p>Review the initial application and send the applicant a decision.</p></div>
        <a class="btn admin-btn-secondary" href="{{ route('dashboard.merchant-applications.index') }}"><i class="fas fa-arrow-left mr-2"></i>All applications</a>
    </div></div></section>

    <section class="content"><div class="container-fluid">
        @if (session('success')) <div class="alert admin-alert-success"><i class="fas fa-check-circle mr-2"></i>{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert admin-alert-error"><i class="fas fa-exclamation-circle mr-2"></i>{{ $errors->first() }}</div> @endif

        <div class="admin-stat-grid">
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-concierge-bell"></i></span><div><small>Requested services</small><strong>{{ count($application->services ?? []) }}</strong><small>{{ implode(', ', $application->serviceLabels()) }}</small></div></article>
            <article class="admin-stat-card"><span class="admin-stat-icon"><i class="fas fa-map-marker-alt"></i></span><div><small>Primary location</small><strong style="font-size:18px">{{ $application->city }}</strong></div></article>
            <article class="admin-stat-card admin-stat-card-red"><span class="admin-stat-icon"><i class="fas fa-percent"></i></span><div><small>Application rate</small><strong>{{ number_format($application->commission_percentage, 2) }}%</strong></div></article>
        </div>

        <div class="row">
            <div class="col-lg-7">
                <div class="card admin-card"><div class="admin-card-header"><div><h2>Business details</h2><p>Information submitted by the applicant</p></div></div><div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Store name</dt><dd class="col-sm-8">{{ $application->business_name }}</dd>
                        <dt class="col-sm-4">Registered name</dt><dd class="col-sm-8">{{ $application->registered_business_name }}</dd>
                        <dt class="col-sm-4">Structure</dt><dd class="col-sm-8">{{ Str::headline($application->business_structure) }}</dd>
                        <dt class="col-sm-4">Cuisine / category</dt><dd class="col-sm-8">{{ $application->cuisine ?: 'Not provided' }}</dd>
                        <dt class="col-sm-4">Branches</dt><dd class="col-sm-8">{{ number_format($application->branch_count) }}</dd>
                        <dt class="col-sm-4">Services</dt><dd class="col-sm-8">{{ implode(', ', $application->serviceLabels()) }}</dd>
                        <dt class="col-sm-4">Description</dt><dd class="col-sm-8" style="white-space: pre-line">{{ $application->business_description ?: 'Not provided' }}</dd>
                        <dt class="col-sm-4">Website / social</dt><dd class="col-sm-8">@if ($application->website)<a href="{{ $application->website }}" target="_blank" rel="noopener">{{ $application->website }}</a>@else Not provided @endif</dd>
                    </dl>
                </div></div>

                <div class="card admin-card"><div class="admin-card-header"><div><h2>Applicant and location</h2><p>Primary contact for follow-up</p></div></div><div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Owner / representative</dt><dd class="col-sm-8">{{ $application->owner_name }}</dd>
                        <dt class="col-sm-4">Email</dt><dd class="col-sm-8"><a href="mailto:{{ $application->email }}">{{ $application->email }}</a></dd>
                        <dt class="col-sm-4">Mobile</dt><dd class="col-sm-8">{{ $application->mobile }}</dd>
                        <dt class="col-sm-4">Telephone</dt><dd class="col-sm-8">{{ $application->telephone ?: 'Not provided' }}</dd>
                        <dt class="col-sm-4">Address</dt><dd class="col-sm-8">{{ $application->address }}, {{ $application->city }}</dd>
                        <dt class="col-sm-4">Submitted</dt><dd class="col-sm-8">{{ $application->created_at?->format('M d, Y · g:i A') }}</dd>
                    </dl>
                </div></div>
            </div>

            <div class="col-lg-5">
                <div class="card admin-card"><div class="admin-card-header"><div><h2>Application review</h2><p>The decision and message are emailed to the applicant</p></div></div><div class="card-body">
                    @if ($application->isPending())
                        <div class="admin-form-note mb-3"><i class="fas fa-info-circle"></i><span>Approval advances this lead to document verification and onboarding. It does not create a merchant account or activate a storefront.</span></div>
                        <form method="POST" action="{{ route('dashboard.merchant-applications.approve', $application) }}">
                            @csrf
                            <div class="form-group"><label for="reviewMessage">Message to applicant <small>(required when declining)</small></label><textarea class="form-control" id="reviewMessage" name="message" rows="6" maxlength="2000" placeholder="Share next steps or explain what is missing">{{ old('message') }}</textarea></div>
                            <div class="d-flex flex-wrap" style="gap: 10px">
                                <button class="btn admin-btn-primary" type="submit"><i class="fas fa-check mr-1"></i>Approve</button>
                                <button class="btn btn-outline-danger" type="submit" formaction="{{ route('dashboard.merchant-applications.decline', $application) }}"><i class="fas fa-times mr-1"></i>Decline</button>
                            </div>
                        </form>
                    @else
                        <p><span class="admin-status {{ $application->status === 'approved' ? 'admin-status-active' : 'admin-status-inactive' }}">{{ Str::headline($application->status) }}</span></p>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Reviewed</dt><dd class="col-sm-8">{{ $application->reviewed_at?->format('M d, Y · g:i A') }}</dd>
                            <dt class="col-sm-4">Message</dt><dd class="col-sm-8" style="white-space: pre-line">{{ $application->review_message ?: 'No message provided' }}</dd>
                        </dl>
                    @endif
                </div></div>

                <div class="card admin-card"><div class="admin-card-header"><div><h2>Onboarding checklist</h2><p>Items to confirm after approval</p></div></div><div class="card-body">
                    <ul class="pl-3 mb-0">
                        <li class="mb-2">Business and tax registration</li>
                        <li class="mb-2">Operating and food permits</li>
                        <li class="mb-2">Applicant identity / authorization</li>
                        <li class="mb-2">Payout account ownership</li>
                        <li class="mb-2">Location, menu, hours, and photos</li>
                        <li>Final commission and service coverage</li>
                    </ul>
                </div></div>
            </div>
        </div>
    </div></section>
</div>
@endsection
