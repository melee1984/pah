@component('mail::message')
# Welcome to the Pahatud Merchant Portal

Hello {{ $merchantName }},

Your Pahatud agent has created the merchant account for **{{ $restaurantName }}**. Use the login details below for your first sign-in.

@component('mail::panel')
**Email:** {{ $emailAddress }}

**Temporary password:** `{{ $temporaryPassword }}`
@endcomponent

@component('mail::button', ['url' => $loginUrl, 'color' => 'error'])
Sign in to Merchant Portal
@endcomponent

For your security, the portal will require you to replace this password immediately after signing in. Do not share this email or password.

Thanks,  
The Pahatud Team
@endcomponent
