@php
    $updateUrl = $agentView ? route('agent.restaurants.update', $restaurant) : route('merchant.application.update');
    $uploadUrl = $agentView ? route('agent.restaurants.documents.store', $restaurant) : route('merchant.application.documents.store');
    $fields = [
        'restaurant_name' => 'Restaurant name', 'registered_business_name' => 'Registered business name',
        'tin' => 'TIN', 'business_registration_number' => 'Business registration number',
        'payout_account_name' => 'Payout account name', 'email' => 'Business email',
        'mobile' => 'Mobile number', 'telephone' => 'Telephone', 'city' => 'City',
        'address' => 'Complete business address', 'description' => 'Restaurant description',
    ];
@endphp
@if (session('success')) <div class="application-feedback application-feedback-success" role="status">{{ session('success') }}</div> @endif
@if ($errors->any()) <div class="application-feedback application-feedback-error" role="alert">{{ $errors->first() }}</div> @endif

<section class="application-panel application-summary">
    <span class="application-eyebrow">Application status</span>
    <div class="application-summary-heading"><h2>{{ $restaurant->restaurant_name }}</h2><span class="application-status application-status-{{ $restaurant->application_status ?: 'pending_review' }}">{{ Str::headline($restaurant->application_status ?: 'Pending review') }}</span></div>
    @if ($restaurant->application_remarks) <p><strong>Admin remarks:</strong> {{ $restaurant->application_remarks }}</p> @endif
    <p class="application-muted">Details and documents can be updated at any time. Changes to an approved application return it to review and pause activation until approved again.</p>
</section>

<section class="application-panel">
    <div class="application-section-heading"><h2>Submitted business information</h2><p>Keep these details accurate so the operations team can verify your restaurant.</p></div>
    <form method="POST" action="{{ $updateUrl }}">
        @csrf @method('PUT')
        <div class="application-grid">
            <div class="application-field"><label for="firstname">Contact first name</label><input id="firstname" name="firstname" value="{{ old('firstname', $contact?->firstname ?: Str::before($contact?->name ?? '', ' ')) }}" required></div>
            <div class="application-field"><label for="lastname">Contact last name</label><input id="lastname" name="lastname" value="{{ old('lastname', $contact?->lastname ?: Str::after($contact?->name ?? '', ' ')) }}" required></div>
            @foreach ($fields as $field => $label)
                <div class="application-field"><label for="{{ $field }}">{{ $label }}</label>
                    @if ($field === 'description')
                        <textarea id="{{ $field }}" name="{{ $field }}">{{ old($field, $restaurant->$field) }}</textarea>
                    @else
                        <input id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $restaurant->$field) }}" @if ($field !== 'telephone') required @endif>
                    @endif
                    @error($field)<span class="application-error">{{ $message }}</span>@enderror
                </div>
            @endforeach
            <div class="application-field"><label for="business_structure">Business structure</label><select id="business_structure" name="business_structure" required>
                @foreach (['sole_proprietorship' => 'Sole proprietorship', 'corporation' => 'Corporation', 'partnership' => 'Partnership', 'cooperative' => 'Cooperative'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('business_structure', $restaurant->business_structure) === $value)>{{ $label }}</option>
                @endforeach
            </select></div>
            <div class="application-field"><label for="enrolling_as">Person enrolling</label><select id="enrolling_as" name="enrolling_as" required>
                <option value="owner" @selected(old('enrolling_as', $restaurant->enrolling_as) === 'owner')>Owner</option>
                <option value="authorized_representative" @selected(old('enrolling_as', $restaurant->enrolling_as) === 'authorized_representative')>Authorized representative</option>
            </select></div>
        </div>
        <div class="application-form-actions"><button class="application-button" type="submit">Save business information</button></div>
    </form>
</section>

<section class="application-panel">
    <div class="application-section-heading"><h2>Documents</h2><p>PDF, JPG, or PNG, up to 10 MB each. Missing files can be supplied later. A replacement returns that document to Pending Verification.</p></div>
    @foreach (\App\RestaurantEnrollmentDocument::LABELS as $type => $label)
        @if ($type !== 'authorization_document' || $restaurant->enrolling_as === 'authorized_representative')
            @php $document = $documents->get($type); $status = $document?->currentStatus() ?? 'missing'; @endphp
            <div class="application-doc">
                <div class="application-doc-heading"><strong>{{ $label }}</strong><span class="application-status application-status-{{ $status }}">{{ Str::headline($status) }}</span></div>
                @if ($document)
                    <div class="application-muted"><a href="{{ $agentView ? route('agent.restaurants.documents.show', [$restaurant, $document]) : route('merchant.application.documents.show', $document) }}" target="_blank" rel="noopener">View {{ $document->original_name }}</a> @if ($document->expires_at) · Expires {{ $document->expires_at->format('M d, Y') }} @endif</div>
                    @if ($document->remarks) <div><strong>Admin remarks:</strong> {{ $document->remarks }}</div> @endif
                @endif
                <form method="POST" action="{{ $uploadUrl }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="document_type" value="{{ $type }}">
                    <input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png" required aria-label="Upload {{ $label }}" data-file-preview>
                    <div class="restaurant-file-preview" aria-live="polite"></div>
                    <button class="application-button" type="submit">{{ $document ? 'Replace file' : 'Upload file' }}</button>
                </form>
            </div>
        @endif
    @endforeach
</section>
