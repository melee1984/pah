<?php

namespace App\Console\Commands;

use App\Agent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreateAgent extends Command
{
    protected $signature = 'agent:create
        {name : Agent full name}
        {email : Agent email address}
        {--mobile= : Mobile number}
        {--commission= : Agent share of Pahatud commission for future qualifying orders}';

    protected $description = 'Create an active Pahatud Agent Portal account';

    public function handle(): int
    {
        $data = [
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'mobile' => $this->option('mobile'),
            'commission_percentage' => $this->option('commission') ?? config('agent.default_commission_percentage'),
            'password' => $this->secret('Password (minimum 8 characters)'),
        ];

        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:agents,email'],
            'mobile' => ['nullable', 'string', 'max:30'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $agent = Agent::query()->create([...$validator->validated(), 'active' => true]);

        $this->info("Agent {$agent->email} created with a {$agent->commission_percentage}% share of Pahatud commission.");

        return self::SUCCESS;
    }
}
