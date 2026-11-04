<?php

namespace App\Mail;

use App\Partners;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MerchantApplicationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Partners $application, public string $invitationUrl) {}

    public function build(): self
    {
        return $this->markdown('emails.merchantApplicationReceived')
            ->subject('Complete your PahatudFood merchant registration');
    }
}
