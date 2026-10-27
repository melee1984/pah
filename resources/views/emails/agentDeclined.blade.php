<x-mail::message>
# Your Agent application was declined

Hello {{ $agent->name }},

Thank you for your interest in the Pahatud Agent Program. After review, your application was declined. Your Agent Dashboard account will remain inactive.

@if ($adminMessage)
**Message from the Pahatud team:**

{{ $adminMessage }}
@endif

Thanks,  
The Pahatud Team
</x-mail::message>
