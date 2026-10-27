<x-mail::message>
# Restaurant application update

Hello {{ $recipientName }},

**{{ $restaurantName }}:** {{ $statusMessage }}

@if ($remarks)
**Remarks:** {{ $remarks }}
@endif

<x-mail::button :url="$detailsUrl">View application</x-mail::button>

Thanks,  
The Pahatud Team
</x-mail::message>
