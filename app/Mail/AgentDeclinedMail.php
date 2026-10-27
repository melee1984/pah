<?php

namespace App\Mail;

use App\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgentDeclinedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Agent $agent, public ?string $adminMessage = null) {}

    public function build(): self
    {
        return $this->markdown('emails.agentDeclined')
            ->subject('Your Pahatud Agent application was declined');
    }
}
