@component('mail::message')
# Welcome to the Pahatud Agent Portal

Hello {{ $agentName }},

An administrator has created your Pahatud agent account. Use the temporary password below for your first sign-in.

@component('mail::panel')
**Temporary password:** `{{ $temporaryPassword }}`
@endcomponent

@component('mail::button', ['url' => $loginUrl, 'color' => 'error'])
Sign in to Agent Portal
@endcomponent

For your security, the portal will require you to replace this password immediately after signing in. Do not share this email or password.

Thanks,  
The Pahatud Team
@endcomponent
