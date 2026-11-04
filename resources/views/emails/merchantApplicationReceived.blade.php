<x-mail::message>
# Complete your merchant registration

Hello {{ $application->owner_name ?: $application->restaurant_name }},

We saved your application for **{{ $application->restaurant_name }}**. You can complete your registration now; you do not need to wait for an approval email.

1. Open the secure link below and choose your password.
2. Confirm your business details and payout information.
3. Upload your valid ID and business registration document. Authorized representatives must also upload an authorization document.

<x-mail::button :url="$invitationUrl">
Complete registration
</x-mail::button>

This link expires in {{ config('agent.restaurant_invitation_expire_hours') }} hours and can only be used once. [Request a new link]({{ route('merchant.register.request-link') }}) if it expires. After setting your password, [sign in]({{ route('merchant.login') }}) to continue at any time.

Your storefront stays inactive while Pahatud reviews your application and documents. We will email you with review updates.

**Registered email:** {{ $application->email }}  
**Requested services:** {{ implode(', ', $application->serviceLabels()) }}  
**Standard commission shown at application:** {{ number_format($application->percentage, 2) }}% of the eligible food order subtotal

Thanks,  
The PahatudFood Team
</x-mail::message>
