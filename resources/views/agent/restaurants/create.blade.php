@extends('agent.layouts.app')

@section('title', 'Enroll Restaurant')

@section('content')
    <link rel="stylesheet" href="{{ asset('css/restaurant-file-preview.css') }}">
    <div class="agent-page-head">
        <div>
            <p class="agent-eyebrow">Grow your network</p>
            <h1>Enroll a restaurant</h1>
            <p>Add a local restaurant to your Pahatud agent portfolio.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="agent-alert agent-alert-error" role="alert">Please review the highlighted fields and try again.</div>
    @endif

    <section class="agent-card agent-form-card">
        <div class="agent-card-header"><div><h2>Restaurant details</h2><p>Provide accurate contact details so the Pahatud team can review the enrollment.</p></div></div>
        <form class="agent-form" method="POST" action="{{ route('agent.restaurants.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="agent-form-note">This enrollment will be linked to your agent ID automatically. The contact will receive a private email invitation to set their password. New restaurants remain under review until Pahatud activates their merchant account.</div>
            <div class="agent-form-grid">
                <div class="agent-field agent-field-full">
                    <label for="restaurant_name">Restaurant name <span class="agent-required">*</span></label>
                    <input class="agent-input @error('restaurant_name') agent-input-error @enderror" id="restaurant_name" name="restaurant_name" value="{{ old('restaurant_name') }}" maxlength="255" required>
                    @error('restaurant_name')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="firstname">Contact first name <span class="agent-required">*</span></label>
                    <input class="agent-input @error('firstname') agent-input-error @enderror" id="firstname" name="firstname" value="{{ old('firstname') }}" maxlength="75" required>
                    @error('firstname')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="lastname">Contact last name <span class="agent-required">*</span></label>
                    <input class="agent-input @error('lastname') agent-input-error @enderror" id="lastname" name="lastname" value="{{ old('lastname') }}" maxlength="75" required>
                    @error('lastname')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="email">Business email <span class="agent-required">*</span></label>
                    <input class="agent-input @error('email') agent-input-error @enderror" id="email" name="email" type="email" value="{{ old('email') }}" required>
                    @error('email')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="mobile">Mobile number <span class="agent-required">*</span></label>
                    <input class="agent-input @error('mobile') agent-input-error @enderror" id="mobile" name="mobile" value="{{ old('mobile') }}" required>
                    @error('mobile')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="telephone">Telephone</label>
                    <input class="agent-input @error('telephone') agent-input-error @enderror" id="telephone" name="telephone" value="{{ old('telephone') }}">
                    @error('telephone')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="city">City <span class="agent-required">*</span></label>
                    <input class="agent-input @error('city') agent-input-error @enderror" id="city" name="city" value="{{ old('city', 'Davao City') }}" required>
                    @error('city')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label for="address">Complete business address <span class="agent-required">*</span></label>
                    <input class="agent-input @error('address') agent-input-error @enderror" id="address" name="address" value="{{ old('address') }}" required>
                    @error('address')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field agent-field-full">
                    <label for="description">Restaurant description</label>
                    <textarea class="agent-input @error('description') agent-input-error @enderror" id="description" name="description" placeholder="Cuisine, specialties, and a short introduction">{{ old('description') }}</textarea>
                    @error('description')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="business_structure">Business structure <span class="agent-required">*</span></label>
                    <select class="agent-input @error('business_structure') agent-input-error @enderror" id="business_structure" name="business_structure" required>
                        <option value="">Select structure</option>
                        @foreach (['sole_proprietorship' => 'Sole proprietorship', 'corporation' => 'Corporation', 'partnership' => 'Partnership', 'cooperative' => 'Cooperative'] as $value => $label)
                            <option value="{{ $value }}" @selected(old('business_structure') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('business_structure')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                <div class="agent-field">
                    <label for="enrolling_as">Person enrolling <span class="agent-required">*</span></label>
                    <select class="agent-input @error('enrolling_as') agent-input-error @enderror" id="enrolling_as" name="enrolling_as" required>
                        <option value="">Select role</option>
                        <option value="owner" @selected(old('enrolling_as') === 'owner')>Owner</option>
                        <option value="authorized_representative" @selected(old('enrolling_as') === 'authorized_representative')>Authorized representative</option>
                    </select>
                    <small>For a corporation, partnership, or cooperative, choose authorized representative.</small>
                    @error('enrolling_as')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
                @foreach (['registered_business_name' => 'Registered business name', 'tin' => 'TIN'] as $field => $label)
                    <div class="agent-field">
                        <label for="{{ $field }}">{{ $label }} <span class="agent-required">*</span></label>
                        <input class="agent-input @error($field) agent-input-error @enderror" id="{{ $field }}" name="{{ $field }}" value="{{ old($field) }}" required>
                        @error($field)<span class="agent-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
                <div class="agent-field agent-field-full">
                    <label for="payout_account_name">Payout account name and account details <span class="agent-required">*</span></label>
                    <textarea class="agent-input @error('payout_account_name') agent-input-error @enderror" id="payout_account_name" name="payout_account_name" maxlength="255" placeholder="Account name, bank or e-wallet, and account number" required>{{ old('payout_account_name') }}</textarea>
                    <small>Enter the account holder name and the details Pahatud should use for payout.</small>
                    @error('payout_account_name')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="agent-card-header agent-document-heading"><div><h2>Required documents</h2><p>Upload the valid ID and business registration certificate now, or add a missing file later from the restaurant account. Both documents must be approved before the restaurant is approved. PDF, JPG, or PNG, up to 10 MB each.</p></div></div>
            <div class="agent-form-grid">
                @foreach ([
                    'government_id' => ['Valid government-issued ID', 'Owner’s ID for a sole proprietorship; authorized representative’s ID for a corporation or partnership.'],
                    'business_registration' => ['Business registration certificate', 'DTI certificate (sole proprietorship), SEC certificate (corporation or partnership), or CDA certificate (cooperative).'],
                ] as $field => [$label, $hint])
                    <div class="agent-field">
                        <label for="{{ $field }}">{{ $label }}</label>
                        <small>{{ $hint }}</small>
                        <input class="agent-input @error($field) agent-input-error @enderror" id="{{ $field }}" name="{{ $field }}" type="file" accept=".pdf,.jpg,.jpeg,.png" data-file-preview>
                        <div class="restaurant-file-preview" aria-live="polite"></div>
                        @error($field)<span class="agent-error">{{ $message }}</span>@enderror
                    </div>
                @endforeach
                <div class="agent-field agent-field-full" id="authorization_document_field">
                    <label for="authorization_document">Authorization document</label>
                    <small>Required before approval when the person enrolling is not the owner. Upload an authorization letter, secretary’s certificate, board resolution, or SPA now or later.</small>
                    <input class="agent-input @error('authorization_document') agent-input-error @enderror" id="authorization_document" name="authorization_document" type="file" accept=".pdf,.jpg,.jpeg,.png" data-file-preview>
                    <div class="restaurant-file-preview" aria-live="polite"></div>
                    @error('authorization_document')<span class="agent-error">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="agent-form-actions">
                <a class="agent-button agent-button-secondary" href="{{ route('agent.restaurants.index') }}">Cancel</a>
                <button class="agent-button agent-button-primary" type="submit">Submit enrollment</button>
            </div>
        </form>
    </section>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const role = document.getElementById('enrolling_as');
            const authorization = document.getElementById('authorization_document');
            function updateAuthorization() {
                const isRepresentative = role.value === 'authorized_representative';
                authorization.closest('.agent-field').style.display = isRepresentative ? '' : 'none';
            }
            role.addEventListener('change', updateAuthorization);
            updateAuthorization();
        });
    </script>
    <script src="{{ asset('js/restaurant-file-preview.js') }}" defer></script>
@endsection
