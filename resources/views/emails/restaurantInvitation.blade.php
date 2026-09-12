@component('mail::message')
# Welcome to Pahatud, {{ $recipientName }}!

Your restaurant, **{{ $restaurantName }}**, has been enrolled as a Pahatud merchant partner.

Complete your account setup to choose a secure password and confirm your contact information.

@component('mail::button', ['url' => $invitationUrl, 'color' => 'error'])
Complete account setup
@endcomponent

This private invitation expires in {{ config('agent.restaurant_invitation_expire_hours') }} hours and can only be used once.
If you were not expecting this invitation, you can safely ignore this email.

Thanks,  
The Pahatud Team
@endcomponent
