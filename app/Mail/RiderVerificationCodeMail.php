<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RiderVerificationCodeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $purpose = 'verification',
    ) {}

    public function build(): self
    {
        $subject = $this->purpose === 'activation'
            ? 'Activate your Pahatud Rider account'
            : 'Pahatud Rider verification code';

        return $this->markdown('emails.riderVerificationCode')
            ->subject($subject);
    }
}
