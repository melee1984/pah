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

Your commission share is assigned automatically from your approved restaurant count:

- **0–34 approved restaurants:** {{ number_format(config('agent.commission_tiers.0'), 0) }}% of Pahatud's commission
- **35–49 approved restaurants:** {{ number_format(config('agent.commission_tiers.35'), 0) }}%
- **50 or more approved restaurants:** {{ number_format(config('agent.commission_tiers.50'), 0) }}%

Only final Approved applications count. A newly reached tier applies to future qualifying orders across your assigned restaurant network, while existing commission entries remain unchanged.

Thanks,  
The Pahatud Team
@endcomponent
