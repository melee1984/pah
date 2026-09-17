<x-mail::message>
# Your Rider application update

Hello {{ $application->full_name }},

We have reviewed your Pahatud Rider application and cannot approve it at this time.

Reason: {{ $application->review_notes }}

Thanks,  
The Pahatud Team
</x-mail::message>
