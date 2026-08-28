<?php

namespace App\Mail;

use App\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgentRegistrationReceivedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Agent $agent) {}

    public function build(): self
    {
        return $this->markdown('emails.agentRegistrationReceived')
            ->subject('We received your Pahatud Agent application');
    }
}
