<?php

namespace App\Console\Commands;

use App\Agent;
use Illuminate\Console\Command;

class SetAgentCommissionRate extends Command
{
    protected $signature = 'agent:set-rate {email : Agent email address} {percentage : Agent share of Pahatud commission, from 0 to 100}';

    protected $description = 'Change an agent share of Pahatud commission for future qualifying orders';

    public function handle(): int
    {
        $percentage = filter_var($this->argument('percentage'), FILTER_VALIDATE_FLOAT);

        if ($percentage === false || $percentage < 0 || $percentage > 100) {
            $this->error('The percentage must be a number from 0 to 100.');

            return self::FAILURE;
        }

        $agent = Agent::query()->where('email', $this->argument('email'))->first();

        if (! $agent) {
            $this->error('No agent was found with that email address.');

            return self::FAILURE;
        }

        $agent->update(['commission_percentage' => round($percentage, 2)]);
        $this->info("Future qualifying orders for {$agent->email} will use a {$agent->commission_percentage}% share of Pahatud commission.");
        $this->line('Existing commission transactions were not changed.');

        return self::SUCCESS;
    }
}
