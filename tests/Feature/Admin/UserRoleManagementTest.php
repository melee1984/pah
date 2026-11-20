<?php

namespace Tests\Feature\Admin;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['firstname', 'lastname', 'mobile'] as $column) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', fn (Blueprint $table) => $table->string($column)->nullable());
            }
        }
    }

    public function test_super_admin_can_view_role_counts_and_users(): void
    {
        $super = $this->user('super@example.com', 'Super', 'Admin');
        $admin = $this->user('admin@example.com', 'Data', 'Admin');
        $member = $this->user('member@example.com', 'Regular', 'Member');
        $super->assignRole(Role::findOrCreate('super', 'web'));
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($super)
            ->get(route('dashboard.user-roles.index'))
            ->assertOk()
            ->assertSee('User roles')
            ->assertSee('Super admins')
            ->assertSee('Dashboard access')
            ->assertSee($admin->email)
            ->assertSee($member->email);
    }

    public function test_admin_can_view_user_roles_but_cannot_assign_super_access(): void
    {
        $admin = $this->user('admin@example.com');
        $target = $this->user('target@example.com');
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($admin)
            ->get(route('dashboard.user-roles.index'))
            ->assertOk()
            ->assertSee('User roles');

        $this->actingAs($admin)
            ->from(route('dashboard.user-roles.index'))
            ->put(route('dashboard.user-roles.update', $target), ['dashboard_role' => 'super'])
            ->assertSessionHasErrors('dashboard_role');

        $this->assertFalse($target->refresh()->hasRole('super'));
    }

    public function test_super_admin_can_assign_dashboard_access_without_removing_other_roles(): void
    {
        $super = $this->user('super@example.com');
        $target = $this->user('partner@example.com');
        $super->assignRole(Role::findOrCreate('super', 'web'));
        $target->assignRole(Role::findOrCreate('partner', 'web'));

        $this->actingAs($super)
            ->put(route('dashboard.user-roles.update', $target), ['dashboard_role' => 'admin'])
            ->assertRedirect();

        $target->refresh();
        $this->assertTrue($target->hasRole('admin'));
        $this->assertTrue($target->hasRole('partner'));
    }

    public function test_manager_role_requires_and_saves_an_assigned_city(): void
    {
        $super = $this->user('super@example.com');
        $target = $this->user('manager@example.com');
        $super->assignRole(Role::findOrCreate('super', 'web'));

        $this->actingAs($super)
            ->from(route('dashboard.user-roles.index'))
            ->put(route('dashboard.user-roles.update', $target), ['dashboard_role' => 'manager'])
            ->assertSessionHasErrors('managed_city');

        $this->actingAs($super)
            ->put(route('dashboard.user-roles.update', $target), [
                'dashboard_role' => 'manager',
                'managed_city' => 'Samal',
            ])
            ->assertRedirect();

        $this->assertTrue($target->refresh()->hasRole('manager'));
        $this->assertSame('Samal', $target->managed_city);
    }

    public function test_super_admin_cannot_change_their_own_access(): void
    {
        $super = $this->user('super@example.com');
        $super->assignRole(Role::findOrCreate('super', 'web'));

        $this->actingAs($super)
            ->from(route('dashboard.user-roles.index'))
            ->put(route('dashboard.user-roles.update', $super), ['dashboard_role' => ''])
            ->assertSessionHasErrors('dashboard_role');

        $this->assertTrue($super->refresh()->hasRole('super'));
    }

    private function user(string $email, string $firstname = 'Test', string $lastname = 'User'): User
    {
        return User::forceCreate([
            'name' => $firstname.' '.$lastname,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }
}
