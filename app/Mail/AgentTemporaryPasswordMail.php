<?php

namespace App\Mail;

use App\Agent;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgentTemporaryPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $agentName;

    public string $loginUrl;

    public function __construct(Agent $agent, public string $temporaryPassword)
    {
        $this->agentName = $agent->name;
        $this->loginUrl = route('agent.login');
    }

    public function build(): self
    {
        return $this->markdown('emails.agentTemporaryPassword')
            ->subject('Your Pahatud Agent Portal account');
    }
}
