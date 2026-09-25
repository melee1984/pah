<x-mail::message>
# Your Agent account is approved

Hello {{ $agent->name }},

Your Pahatud Agent application has been approved. You can now sign in using the email address and password you provided during registration.

@if ($adminMessage)
**Message from the Pahatud team:**

{{ $adminMessage }}
@endif

<x-mail::button :url="$loginUrl">
Open Agent Dashboard
</x-mail::button>

Your current agent share is **{{ number_format($agent->commissionPercentage(), 2) }}% of Pahatud's commission** from each qualifying successful restaurant order. Your share increases automatically at 35 and 50 approved restaurants.

Thanks,  
The Pahatud Team
</x-mail::message>
