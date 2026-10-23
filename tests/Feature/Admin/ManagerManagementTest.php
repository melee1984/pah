<?php

namespace Tests\Feature\Admin;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('partners')) {
            Schema::create('partners', function (Blueprint $table) {
                $table->id();
                $table->string('city')->nullable();
                $table->timestamps();
            });
        }

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('user', 'web');
        Role::findOrCreate('manager', 'web');
    }

    public function test_administrator_can_assign_update_and_revoke_manager_access(): void
    {
        $admin = $this->user('admin@example.com');
        $admin->assignRole('admin');
        $candidate = $this->user('candidate@example.com');
        $candidate->assignRole('user');

        $this->actingAs($admin)
            ->get(route('dashboard.managers.index'))
            ->assertOk()
            ->assertSee('Manager access');

        $this->post(route('dashboard.managers.store'), [
            'email' => $candidate->email,
            'managed_city' => 'Samal',
        ])->assertRedirect()->assertSessionHas('success');

        $candidate->refresh();
        $this->assertTrue($candidate->hasRole('manager'));
        $this->assertTrue($candidate->hasRole('user'));
        $this->assertSame('Samal', $candidate->managed_city);

        $this->put(route('dashboard.managers.update', $candidate), [
            'managed_city' => 'Davao City',
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame('Davao City', $candidate->fresh()->managed_city);

        $this->delete(route('dashboard.managers.destroy', $candidate))
            ->assertRedirect()->assertSessionHas('success');
        $candidate->refresh();
        $this->assertFalse($candidate->hasRole('manager'));
        $this->assertTrue($candidate->hasRole('user'));
        $this->assertNull($candidate->managed_city);
    }

    public function test_city_manager_cannot_manage_roles(): void
    {
        $manager = $this->user('manager@example.com', 'Samal');
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('dashboard.managers.index'))
            ->assertForbidden();
    }

    public function test_administrator_account_cannot_be_converted_to_manager(): void
    {
        $admin = $this->user('admin@example.com');
        $admin->assignRole('admin');
        $otherAdmin = $this->user('other-admin@example.com');
        $otherAdmin->assignRole('admin');

        $this->actingAs($admin)
            ->post(route('dashboard.managers.store'), [
                'email' => $otherAdmin->email,
                'managed_city' => 'Samal',
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertFalse($otherAdmin->fresh()->hasRole('manager'));
    }

    private function user(string $email, ?string $managedCity = null): User
    {
        return User::query()->forceCreate([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('password'),
            'managed_city' => $managedCity,
        ]);
    }
}
