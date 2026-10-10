<x-mail::message>
# Merchant application received

Hello {{ $application->owner_name }},

Thank you for applying to join PahatudFood. We saved the application for **{{ $application->business_name }}** and it is now awaiting review.

**Registered email:** {{ $application->email }}  
**Requested services:** {{ implode(', ', $application->serviceLabels()) }}  
**Standard commission shown at application:** {{ number_format($application->commission_percentage, 2) }}% of the eligible food order subtotal

We will email you again after an administrator approves or declines the application. If approved, the next step is document verification and merchant onboarding; the storefront is not activated by this initial application alone.

Thanks,  
The PahatudFood Team
</x-mail::message>
