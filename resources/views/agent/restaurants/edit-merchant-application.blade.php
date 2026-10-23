@extends('agent.layouts.app')

@section('title', 'Update Merchant Application')

@section('content')
    <div class="agent-page-head">
        <div>
            <p class="agent-eyebrow">Merchant referral</p>
            <h1>Update merchant application</h1>
            <p>Correct the applicant’s details, then submit the application for admin review.</p>
        </div>
        <span class="agent-badge agent-badge-{{ $application->recruitment_status === 'declined' ? 'reversed' : $application->recruitment_status }}">{{ $application->recruitmentStatusLabel() }}</span>
    </div>

    @if ($errors->any())
        <div class="agent-alert agent-alert-error" role="alert">Please review the highlighted fields and try again.</div>
    @endif

    <section class="agent-card agent-form-card">
        <div class="agent-card-header"><div><h2>Application details</h2><p>Changes are recorded on the merchant’s application and sent back to the admin review queue.</p></div></div>
        <form class="agent-form" method="POST" action="{{ route('agent.merchant-applications.update', $application) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="agent-form-note">Saving this form submits the application as <strong>Pending Approval</strong>. An administrator must approve it before the merchant can continue through recruitment.</div>

            @if ($application->recruitment_review_message)
                <div class="agent-alert agent-alert-warning"><strong>Admin feedback:</strong> {{ $application->recruitment_review_message }}</div>
            @endif

            <div class="agent-form-grid">
                <div class="agent-field">
                    <label for="restaurant_name">Store or restaurant name <span class="agent-required">*</span></label>
                    <input class="agent-input @error('restaurant_name') agent-input-error @enderror" id="restaurant_name" name="restaurant_name" value="{{ old('restaurant_name', $application->restaurant_name) }}" maxlength="255" required>
                    @error('restaurant_name')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="registered_business_name">Registered business name <span class="agent-required">*</span></label>
                    <input class="agent-input @error('registered_business_name') agent-input-error @enderror" id="registered_business_name" name="registered_business_name" value="{{ old('registered_business_name', $application->registered_business_name) }}" maxlength="255" required>
                    @error('registered_business_name')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="owner_name">Owner / authorized representative <span class="agent-required">*</span></label>
                    <input class="agent-input @error('owner_name') agent-input-error @enderror" id="owner_name" name="owner_name" value="{{ old('owner_name', $application->owner_name) }}" maxlength="255" required>
                    @error('owner_name')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="business_structure">Business structure <span class="agent-required">*</span></label>
                    <select class="agent-input @error('business_structure') agent-input-error @enderror" id="business_structure" name="business_structure" required>
                        <option value="">Select structure</option>
                        @foreach (['sole_proprietorship' => 'Sole proprietorship', 'corporation' => 'Corporation', 'partnership' => 'Partnership', 'cooperative' => 'Cooperative'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('business_structure', $application->business_structure) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('business_structure')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="enrolling_as">Person enrolling <span class="agent-required">*</span></label>
                    <select class="agent-input @error('enrolling_as') agent-input-error @enderror" id="enrolling_as" name="enrolling_as" required>
                        <option value="">Select role</option>
                        <option value="owner" @selected(old('enrolling_as', $application->enrolling_as) === 'owner')>Owner</option>
                        <option value="authorized_representative" @selected(old('enrolling_as', $application->enrolling_as) === 'authorized_representative')>Authorized representative</option>
                    </select>
                    @error('enrolling_as')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="email">Business email <span class="agent-required">*</span></label>
                    <input class="agent-input @error('email') agent-input-error @enderror" id="email" name="email" type="email" value="{{ old('email', $application->email) }}" maxlength="255" required>
                    @error('email')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="mobile">Mobile number <span class="agent-required">*</span></label>
                    <input class="agent-input @error('mobile') agent-input-error @enderror" id="mobile" name="mobile" value="{{ old('mobile', $application->mobile) }}" maxlength="30" required>
                    @error('mobile')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="telephone">Telephone</label>
                    <input class="agent-input @error('telephone') agent-input-error @enderror" id="telephone" name="telephone" value="{{ old('telephone', $application->telephone) }}" maxlength="30">
                    @error('telephone')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="cuisine">Cuisine / food category</label>
                    <input class="agent-input @error('cuisine') agent-input-error @enderror" id="cuisine" name="cuisine" value="{{ old('cuisine', $application->cuisine) }}" maxlength="120">
                    @error('cuisine')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label for="address">Primary business address <span class="agent-required">*</span></label>
                    <input class="agent-input @error('address') agent-input-error @enderror" id="address" name="address" value="{{ old('address', $application->address) }}" maxlength="500" required>
                    @error('address')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="city">City / municipality <span class="agent-required">*</span></label>
                    <input class="agent-input @error('city') agent-input-error @enderror" id="city" name="city" value="{{ old('city', $application->city) }}" maxlength="120" required>
                    @error('city')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="branch_count">Number of branches <span class="agent-required">*</span></label>
                    <input class="agent-input @error('branch_count') agent-input-error @enderror" id="branch_count" name="branch_count" type="number" value="{{ old('branch_count', $application->branch_count ?: 1) }}" min="1" max="500" required>
                    @error('branch_count')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="tin">TIN <span class="agent-required">*</span></label>
                    <input class="agent-input @error('tin') agent-input-error @enderror" id="tin" name="tin" value="{{ old('tin', $application->tin) }}" maxlength="30" required>
                    @error('tin')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="business_registration_number">Business registration number</label>
                    <input class="agent-input @error('business_registration_number') agent-input-error @enderror" id="business_registration_number" name="business_registration_number" value="{{ old('business_registration_number', $application->business_registration_number) }}" maxlength="100">
                    @error('business_registration_number')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label>Services the merchant wants to offer <span class="agent-required">*</span></label>
                    <div class="merchant-service-options">
                        @foreach ($services as $value => $label)
                            <label><input name="services[]" type="checkbox" value="{{ $value }}" @checked(in_array($value, old('services', $application->services ?? []), true))><span><strong>{{ $label }}</strong></span></label>
                        @endforeach
                    </div>
                    @error('services')<span class="agent-error">{{ $message }}</span>@enderror
                    @error('services.*')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label for="website">Website or social page</label>
                    <input class="agent-input @error('website') agent-input-error @enderror" id="website" name="website" type="url" value="{{ old('website', $application->website) }}" maxlength="255" placeholder="https://">
                    @error('website')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label for="description">Business description</label>
                    <textarea class="agent-input @error('description') agent-input-error @enderror" id="description" name="description" maxlength="2000">{{ old('description', $application->description) }}</textarea>
                    @error('description')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label for="payout_account_name">Payout account name and account details <span class="agent-required">*</span></label>
                    <textarea class="agent-input @error('payout_account_name') agent-input-error @enderror" id="payout_account_name" name="payout_account_name" maxlength="255" placeholder="Account name, bank or e-wallet, and account number" required>{{ old('payout_account_name', $application->payout_account_name) }}</textarea>
                    <small>Enter the account holder name and the details Pahatud should use for payout.</small>
                    @error('payout_account_name')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="agent-card-header agent-document-heading"><div><h2>Required documents</h2><p>Upload the valid ID and business registration certificate now, or add a missing file later. Replacing a file sends it back for verification. PDF, JPG, or PNG, up to 10 MB each.</p></div></div>
            <div class="agent-form-grid">
                @foreach ([
                    'government_id' => ['Valid government-issued ID', 'Owner’s ID for a sole proprietorship; authorized representative’s ID for a corporation or partnership.'],
                    'business_registration' => ['Business registration certificate', 'DTI certificate (sole proprietorship), SEC certificate (corporation or partnership), or CDA certificate (cooperative).'],
                ] as $field => [$label, $hint])
                    @php $document = $documents->get($field); @endphp
                    <div class="agent-field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <small>{{ $hint }}</small>
                        @if ($document)
                            <small><a class="agent-text-link" href="{{ route('agent.restaurants.documents.show', [$application, $document]) }}" target="_blank" rel="noopener">View {{ $document->original_name }}</a> · {{ Str::headline($document->currentStatus()) }}</small>
                        @endif
                        <input class="agent-input @error($field) agent-input-error @enderror" id="{{ $field }}" name="{{ $field }}" type="file" accept=".pdf,.jpg,.jpeg,.png" data-file-preview>
                        <div class="restaurant-file-preview" aria-live="polite"></div>
                        @error($field)<span class="agent-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
                @php $authorizationDocument = $documents->get('authorization_document'); @endphp
                <div class="agent-field agent-field-full" id="authorization_document_field">
                    <label for="authorization_document">Authorization document</label>
                    <small>Required before final approval when the person enrolling is an authorized representative.</small>
                    @if ($authorizationDocument)
                        <small><a class="agent-text-link" href="{{ route('agent.restaurants.documents.show', [$application, $authorizationDocument]) }}" target="_blank" rel="noopener">View {{ $authorizationDocument->original_name }}</a> · {{ Str::headline($authorizationDocument->currentStatus()) }}</small>
                    @endif
                    <input class="agent-input @error('authorization_document') agent-input-error @enderror" id="authorization_document" name="authorization_document" type="file" accept=".pdf,.jpg,.jpeg,.png" data-file-preview>
                    <div class="restaurant-file-preview" aria-live="polite"></div>
                    @error('authorization_document')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="agent-form-actions">
                <a class="agent-button agent-button-secondary" href="{{ route('agent.restaurants.index') }}#referral-signups">Cancel</a>
                <button class="agent-button agent-button-primary" type="submit">Save and submit for approval</button>
            </div>
        </form>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const role = document.getElementById('enrolling_as');
            const authorizationField = document.getElementById('authorization_document_field');
            function updateAuthorization() {
                authorizationField.style.display = role.value === 'authorized_representative' ? '' : 'none';
            }
            role.addEventListener('change', updateAuthorization);
            updateAuthorization();
        });
    </script>
@endsection
