@extends('agent.layouts.app')

@section('title', 'Enroll Restaurant')

@section('content')
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
        <form class="agent-form" method="POST" action="{{ route('agent.restaurants.store') }}">
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
            </div>
            <div class="agent-form-actions">
                <a class="agent-button agent-button-secondary" href="{{ route('agent.restaurants.index') }}">Cancel</a>
                <button class="agent-button agent-button-primary" type="submit">Submit enrollment</button>
            </div>
        </form>
    </section>
@endsection
