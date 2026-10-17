<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class AssignManager extends Command
{
    protected $signature = 'admin:assign-manager {email : Existing admin user email} {city : City this manager may access}';

    protected $description = 'Assign the manager role and city jurisdiction to an existing user';

    public function handle(): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user exists with that email address.');

            return self::FAILURE;
        }

        $city = trim((string) $this->argument('city'));
        if ($city === '') {
            $this->error('City cannot be empty.');

            return self::FAILURE;
        }

        Role::findOrCreate('manager', 'web');
        $user->forceFill(['managed_city' => $city])->save();
        $user->syncRoles(['manager']);

        $this->info("{$user->email} is now the manager for {$city}.");

        return self::SUCCESS;
    }
}
