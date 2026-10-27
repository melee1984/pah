<x-mail::message>
# Application received

Hello {{ $agent->name }},

Thank you for applying to the Pahatud Agent Program. Your application is now awaiting review by the Pahatud operations team.

Your account cannot sign in until it is approved. We will email you again with the review decision.

**Registered email:** {{ $agent->email }}  
**Starting agent share:** {{ number_format($agent->commissionPercentage(), 2) }}% of Pahatud's commission from each qualifying order.

Your automatic commission tiers are:

- **0–34 approved restaurants:** {{ number_format(config('agent.commission_tiers.0'), 0) }}%
- **35–49 approved restaurants:** {{ number_format(config('agent.commission_tiers.35'), 0) }}%
- **50 or more approved restaurants:** {{ number_format(config('agent.commission_tiers.50'), 0) }}%

Only final Approved restaurant applications count. A newly reached tier applies to future qualifying orders from every restaurant assigned to you. Existing commission entries keep their original rate and amount.

Thanks,  
The Pahatud Team
</x-mail::message>
