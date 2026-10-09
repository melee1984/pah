@extends('templates.customer-account')
@section('title', 'My Profile')
@section('content')
<div class="customer-profile-grid">
    <section class="customer-card"><h2>Account information</h2><p>These details belong to the account you use across PahatudFood and the Support Center.</p><p><strong>{{ $user->full_name }}</strong><br>{{ $user->email }}</p></section>
    <form class="customer-card customer-profile-form" method="POST" action="{{ route('profile.update') }}">
        @csrf @method('PATCH')
        <h2>Update your profile</h2>
        @foreach(['firstname' => 'First name', 'lastname' => 'Last name', 'email' => 'Email address', 'mobile' => 'Mobile number'] as $field => $label)
            <label for="profile-{{ $field }}">{{ $label }}</label>
            <input id="profile-{{ $field }}" name="{{ $field }}" type="{{ $field === 'email' ? 'email' : ($field === 'mobile' ? 'tel' : 'text') }}" value="{{ old($field, $user->$field) }}" required maxlength="{{ $field === 'email' ? 255 : ($field === 'mobile' ? 30 : 100) }}" autocomplete="{{ ['firstname' => 'given-name', 'lastname' => 'family-name', 'email' => 'email', 'mobile' => 'tel'][$field] }}" @error($field) aria-invalid="true" aria-describedby="error-{{ $field }}" @enderror>
            @error($field)<p class="customer-field-error" id="error-{{ $field }}" role="alert">{{ $message }}</p>@enderror
        @endforeach
        <button class="customer-auth-submit" type="submit">Save changes</button>
    </form>
</div>
@endsection
