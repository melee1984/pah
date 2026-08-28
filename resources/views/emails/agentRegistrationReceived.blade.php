<x-mail::message>
# Application received

Hello {{ $agent->name }},

Thank you for applying to the Pahatud Agent Program. Your application is now awaiting review by the Pahatud operations team.

Your account cannot sign in until it is approved. We will email you again when access to the Agent Dashboard is enabled.

**Registered email:** {{ $agent->email }}  
**Starting agent share:** {{ number_format($agent->commission_percentage, 2) }}% of Pahatud's commission from each qualifying order

Thanks,  
The Pahatud Team
</x-mail::message>
