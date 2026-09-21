<x-mail::message>
# {{ $purpose === 'activation' ? 'Activate your Rider account' : 'Your Rider verification code' }}

Use this code to continue:

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

This code expires in 10 minutes. If you did not request it, you can safely ignore this email.

Thanks,  
The Pahatud Team
</x-mail::message>
