<?php

namespace Tests\Feature\Admin;

use App\Agent;
use App\Services\AdminCityScope;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCityScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('partners')) {
            Schema::create('partners', function (Blueprint $table) {
                $table->id();
                $table->foreignId('agent_id')->nullable();
                $table->string('city')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order')) {
            Schema::create('order', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable();
                $table->foreignId('partner_id')->nullable();
            });
        }
    }

    public function test_manager_only_sees_agents_in_the_assigned_city(): void
    {
        $manager = $this->userWithRole('manager', 'Samal');
        $this->actingAs($manager);

        $samal = Agent::query()->create($this->agentAttributes('Samal Agent', 'samal@example.com', 'Samal'));
        Agent::query()->create($this->agentAttributes('Davao Agent', 'davao@example.com', 'Davao City'));
        $restaurantAgent = Agent::query()->create($this->agentAttributes('Restaurant Agent', 'restaurant@example.com', null));
        $restaurantAgent->restaurants()->create(['city' => ' samal ']);

        $visibleIds = app(AdminCityScope::class)
            ->agents(Agent::query())
            ->pluck('id')
            ->all();

        $this->assertEqualsCanonicalizing([$samal->id, $restaurantAgent->id], $visibleIds);
        $this->assertTrue($manager->isAdmin());
    }

    public function test_admin_and_super_roles_are_not_city_restricted(): void
    {
        Agent::query()->create($this->agentAttributes('Samal Agent', 'samal@example.com', 'Samal'));
        Agent::query()->create($this->agentAttributes('Davao Agent', 'davao@example.com', 'Davao City'));

        foreach (['admin', 'super'] as $role) {
            $user = $this->userWithRole($role, 'Samal', $role.'@example.com');
            $this->actingAs($user);

            $this->assertFalse(app(AdminCityScope::class)->restricted());
            $this->assertCount(2, app(AdminCityScope::class)->agents(Agent::query())->get());
        }
    }

    public function test_manager_scopes_customers_and_riders_by_city(): void
    {
        $manager = $this->userWithRole('manager', 'Samal');
        $this->actingAs($manager);
        $samalCustomer = $this->customer('samal-customer@example.com', 'Samal');
        $orderedCustomer = $this->customer('ordered-customer@example.com', null);
        $this->customer('davao-customer@example.com', 'Davao City');

        $partnerId = DB::table('partners')->insertGetId([
            'city' => 'Samal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('order')->insert(['user_id' => $orderedCustomer->id, 'partner_id' => $partnerId]);

        $samalRiderId = DB::table('rider')->insertGetId([
            'name' => 'Samal Rider',
            'city' => 'Samal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rider')->insert([
            'name' => 'Davao Rider',
            'city' => 'Davao City',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scope = app(AdminCityScope::class);
        $this->assertEqualsCanonicalizing(
            [$samalCustomer->id, $orderedCustomer->id],
            $scope->users(User::query())->pluck('id')->all(),
        );
        $this->assertSame([$samalRiderId], $scope->riders(DB::table('rider'))->pluck('id')->all());
    }

    private function userWithRole(string $role, ?string $managedCity, string $email = 'manager@example.com'): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::query()->forceCreate([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => bcrypt('password'),
            'managed_city' => $managedCity,
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function agentAttributes(string $name, string $email, ?string $city): array
    {
        return [
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'city' => $city,
            'commission_percentage' => 15,
            'active' => true,
            'must_change_password' => false,
        ];
    }

    private function customer(string $email, ?string $city): User
    {
        return User::query()->forceCreate([
            'name' => 'Customer',
            'email' => $email,
            'password' => bcrypt('password'),
            'city' => $city,
        ]);
    }
}
