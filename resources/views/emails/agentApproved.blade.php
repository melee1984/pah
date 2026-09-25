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

Your current agent share is **{{ number_format($agent->commissionPercentage(), 2) }}% of Pahatud's commission** from each qualifying successful restaurant order.

Your automatic commission tiers are:

- **0–34 approved restaurants:** {{ number_format(config('agent.commission_tiers.0'), 0) }}%
- **35–49 approved restaurants:** {{ number_format(config('agent.commission_tiers.35'), 0) }}%
- **50 or more approved restaurants:** {{ number_format(config('agent.commission_tiers.50'), 0) }}%

Only final Approved restaurant applications count. When you reach a new tier, it applies to future qualifying orders from all restaurants assigned to you—including those enrolled earlier. Existing commission entries never change.

Thanks,  
The Pahatud Team
</x-mail::message>
