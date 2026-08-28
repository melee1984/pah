<?php

namespace App\Mail;

use App\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgentApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;

    public function __construct(public Agent $agent)
    {
        $this->loginUrl = route('agent.login');
    }

    public function build(): self
    {
        return $this->markdown('emails.agentApproved')
            ->subject('Your Pahatud Agent account is approved');
    }
}
