<x-mail::message>
@if ($application->status === \App\MerchantApplication::STATUS_APPROVED)
# Your merchant application is approved

Hello {{ $application->owner_name }},

Your application for **{{ $application->business_name }}** has been approved for the next stage of PahatudFood onboarding.

Approval of this application does not activate the storefront yet. The PahatudFood team will guide you through business-document verification, merchant account setup, menu preparation, and final activation.
@else
# Update on your merchant application

Hello {{ $application->owner_name }},

Thank you for your interest in PahatudFood. After review, the application for **{{ $application->business_name }}** was declined.
@endif

@if ($adminMessage)
**Message from the PahatudFood team:**

{{ $adminMessage }}
@endif

Thanks,  
The PahatudFood Team
</x-mail::message>
