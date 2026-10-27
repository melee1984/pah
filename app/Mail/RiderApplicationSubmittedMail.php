<?php

namespace App\Mail;

use App\RiderApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RiderApplicationSubmittedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public RiderApplication $application) {}

    public function build(): self
    {
        return $this->markdown('emails.riderApplicationSubmitted')
            ->subject('Your Pahatud Rider application is under review');
    }
}
