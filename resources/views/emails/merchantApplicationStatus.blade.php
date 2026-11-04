<x-mail::message>
@if ($application->recruitment_status === \App\Partners::STATUS_APPROVED)
# Your merchant application is approved

Hello {{ $application->owner_name }},

Your application for **{{ $application->restaurant_name }}** has been approved for the next stage of PahatudFood onboarding.

Approval of this application does not activate the storefront yet. Sign in to complete your business details, upload any missing documents, and prepare your menu. If you have not set your password yet, use the setup link sent when you applied.
<x-mail::button :url="route('merchant.application.show')">
Continue your application
</x-mail::button>

Need a new setup link? [Request one here]({{ route('merchant.register.request-link') }}).
@else
# Update on your merchant application

Hello {{ $application->owner_name }},

Thank you for your interest in PahatudFood. After review, the application for **{{ $application->restaurant_name }}** was declined.
@endif

@if ($adminMessage)
**Message from the PahatudFood team:**

{{ $adminMessage }}
@endif

Thanks,  
The PahatudFood Team
</x-mail::message>
