<?php

namespace App\Console\Commands;

use App\Agent;
use Illuminate\Console\Command;

class SetAgentCommissionRate extends Command
{
    protected $signature = 'agent:set-rate {email : Agent email address} {percentage? : Deprecated and ignored}';

    protected $description = 'Show the automatic tiered commission rate for an agent';

    public function handle(): int
    {
        $agent = Agent::query()->where('email', $this->argument('email'))->first();

        if (! $agent) {
            $this->error('No agent was found with that email address.');

            return self::FAILURE;
        }

        $approvedRestaurantCount = $agent->approvedRestaurantCount();
        $this->error('Agent rates are assigned automatically and cannot be changed manually.');
        $this->line("{$agent->email} has {$approvedRestaurantCount} approved restaurants and a {$agent->commissionPercentage($approvedRestaurantCount)}% share.");
        $this->line('Any percentage argument is ignored, and existing commission transactions are not changed.');

        return self::FAILURE;
    }
}
