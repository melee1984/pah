@extends('dashboard.template.main2')

@section('content')
<div class="content-wrapper admin-content-wrapper">
    <section class="content-header"><div class="container-fluid"><div class="admin-page-heading"><div><span class="admin-eyebrow">Restaurant application</span><h1>{{ $restaurant->restaurant_name }}</h1><p>Review submitted business information and each document before deciding.</p></div><a href="{{ route('dashboard.merchant') }}">Back to merchants</a></div></div></section>
    <section class="content"><div class="container-fluid">
        @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
        @if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
        <div class="card admin-card"><div class="card-body">
            <h2>Business information</h2>
            <dl class="row">
                @foreach ([
                    'Restaurant' => $restaurant->restaurant_name, 'Registered business name' => $restaurant->registered_business_name,
                    'Structure' => Str::headline($restaurant->business_structure ?: 'Not provided'), 'Enrolling as' => Str::headline($restaurant->enrolling_as ?: 'Not provided'),
                    'TIN' => $restaurant->tin, 'Registration number' => $restaurant->business_registration_number,
                    'Payout account name' => $restaurant->payout_account_name,
                    'Contact' => $restaurant->enrollmentContact?->name ?: trim(($restaurant->enrollmentContact?->firstname ?? '').' '.($restaurant->enrollmentContact?->lastname ?? '')),
                    'Email' => $restaurant->email,
                    'Mobile' => $restaurant->mobile, 'Telephone' => $restaurant->telephone,
                    'Address' => $restaurant->address, 'City' => $restaurant->city,
                    'Description' => $restaurant->description, 'Agent' => $restaurant->agent?->name,
                ] as $label => $value)
                    <dt class="col-md-3">{{ $label }}</dt><dd class="col-md-9">{{ $value ?: '—' }}</dd>
                @endforeach
            </dl>
            <p><strong>Application:</strong> {{ Str::headline($restaurant->application_status ?: 'Pending review') }} @if ($restaurant->application_remarks) — {{ $restaurant->application_remarks }} @endif</p>
        </div></div>
        <div class="card admin-card"><div class="card-body"><h2>Documents</h2><p>Check the file contents, names, location, and validity dates before approving. Reject a file with remarks to request a replacement.</p>
            @foreach (\App\RestaurantEnrollmentDocument::LABELS as $type => $label)
                @if ($type !== 'authorization_document' || $restaurant->enrolling_as === 'authorized_representative')
                    @php $document = $documents->get($type); @endphp
                    <div class="border-top py-3">
                        <strong>{{ $label }}</strong> — {{ Str::headline($document?->currentStatus() ?? 'missing') }}
                        @if ($document)
                            <p><a href="{{ route('dashboard.merchant.documents.show', [$restaurant, $document]) }}" target="_blank" rel="noopener">View {{ $document->original_name }}</a> @if ($document->expires_at) · Expires {{ $document->expires_at->format('M d, Y') }} @endif</p>
                            @if ($document->remarks) <p><strong>Previous remarks:</strong> {{ $document->remarks }}</p> @endif
                            <form method="POST" action="{{ route('dashboard.merchant.documents.review', [$restaurant->id, $document]) }}">
                                @csrf
                                <div class="form-row"><div class="form-group col-md-3"><label>Status</label><select class="form-control" name="status"><option value="approved">Approved</option><option value="rejected" @selected($document->status === 'rejected')>Rejected / request replacement</option></select></div>
                                <div class="form-group col-md-3"><label>Expiration date</label><input class="form-control" name="expires_at" type="date" value="{{ $document->expires_at?->format('Y-m-d') }}"></div>
                                <div class="form-group col-md-6"><label>Remarks</label><input class="form-control" name="remarks" maxlength="2000" value="{{ $document->remarks }}" placeholder="Reason or replacement instructions"></div></div>
                                <button class="btn admin-btn-primary" type="submit">Save document review</button>
                            </form>
                        @else
                            <p class="text-muted">The restaurant can upload this file from its account. Approval remains unavailable.</p>
                        @endif
                    </div>
                @endif
            @endforeach
        </div></div>
        <div class="card admin-card"><div class="card-body"><h2>Application decision</h2><p>Approve is allowed only when business information is complete and every required document has an Approved status and is not expired.</p>
            <form method="POST" action="{{ route('dashboard.merchant.application.review', $restaurant->id) }}">
                @csrf
                <div class="form-group"><label for="applicationRemarks">Remarks to restaurant and agent</label><textarea class="form-control" id="applicationRemarks" name="remarks" rows="3" maxlength="2000">{{ old('remarks', $restaurant->application_remarks) }}</textarea></div>
                <button class="btn admin-btn-primary" name="decision" value="approved" type="submit" @disabled(! $restaurant->applicationReadyForApproval())>Approve application</button>
                <button class="btn btn-outline-danger" name="decision" value="declined" type="submit">Decline application</button>
            </form>
        </div></div>
    </div></section>
</div>
@endsection
