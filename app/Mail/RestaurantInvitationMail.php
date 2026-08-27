<?php

namespace App\Mail;

use App\RestaurantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RestaurantInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $invitationUrl;

    public string $restaurantName;

    public string $recipientName;

    public function __construct(RestaurantInvitation $invitation, string $plainToken, string $recipientName)
    {
        $this->invitationUrl = route('restaurant.invitation.show', $plainToken);
        $this->restaurantName = $invitation->restaurant->restaurant_name;
        $this->recipientName = $recipientName;
        $this->withSymfonyMessage(function ($message) use ($invitation) {
            $message->getHeaders()->addTextHeader('X-Pahatud-Invitation-ID', (string) $invitation->getKey());
        });
    }

    public function build(): self
    {
        return $this->markdown('emails.restaurantInvitation')
            ->subject('Complete your Pahatud restaurant account');
    }
}
