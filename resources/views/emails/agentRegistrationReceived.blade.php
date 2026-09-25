<x-mail::message>
# Application received

Hello {{ $agent->name }},

Thank you for applying to the Pahatud Agent Program. Your application is now awaiting review by the Pahatud operations team.

Your account cannot sign in until it is approved. We will email you again with the review decision.

**Registered email:** {{ $agent->email }}  
**Starting agent share:** {{ number_format($agent->commissionPercentage(), 2) }}% of Pahatud's commission from each qualifying order. Your share increases automatically as more of your enrolled restaurants are approved.

Thanks,  
The Pahatud Team
</x-mail::message>
