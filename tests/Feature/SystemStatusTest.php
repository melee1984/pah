<?php

namespace Tests\Feature;

use App\SystemIncident;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SystemStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('manager', 'web');
    }

    public function test_public_status_page_shows_operational_when_there_are_no_active_issues(): void
    {
        $this->get(route('system-status.index'))
            ->assertOk()
            ->assertSee('System Status')
            ->assertSee('Operational')
            ->assertSee('No active issues')
            ->assertSee(route('system-status.index'), false);
    }

    public function test_public_status_page_prioritizes_maintenance_and_shows_active_and_resolved_issues(): void
    {
        Carbon::setTestNow('2026-10-28 14:30:00');

        SystemIncident::query()->create([
            'title' => 'Delayed order updates',
            'description' => 'Some order tracking updates may arrive late.',
            'type' => SystemIncident::TYPE_ISSUE,
            'status' => SystemIncident::STATUS_IN_PROGRESS,
        ]);
        SystemIncident::query()->create([
            'title' => 'Payment maintenance',
            'description' => 'Card payments are temporarily paused during maintenance.',
            'type' => SystemIncident::TYPE_MAINTENANCE,
            'status' => SystemIncident::STATUS_INVESTIGATING,
        ]);
        SystemIncident::query()->create([
            'title' => 'Restaurant search restored',
            'description' => 'Search results are displaying normally again.',
            'type' => SystemIncident::TYPE_ISSUE,
            'status' => SystemIncident::STATUS_RESOLVED,
            'resolved_at' => now()->subHour(),
        ]);

        $this->get(route('system-status.index'))
            ->assertOk()
            ->assertSee('Under Maintenance')
            ->assertSee('Delayed order updates')
            ->assertSee('Fix in Progress')
            ->assertSee('Payment maintenance')
            ->assertSee('Restaurant search restored')
            ->assertSee('Oct 28, 2026 · 2:30 PM PHT');
    }

    public function test_administrator_can_create_and_advance_an_issue_to_resolved(): void
    {
        Carbon::setTestNow('2026-10-28 09:15:00');
        $admin = $this->user('admin@example.test', 'admin');

        $this->actingAs($admin)
            ->get(route('dashboard.system-status.index'))
            ->assertOk()
            ->assertSee('Create an issue');

        $this->post(route('dashboard.system-status.store'), [
            'title' => 'Checkout interruption',
            'description' => 'Customers may be unable to place new orders.',
            'type' => SystemIncident::TYPE_ISSUE,
            'status' => SystemIncident::STATUS_INVESTIGATING,
        ])->assertRedirect()->assertSessionHas('success');

        $incident = SystemIncident::query()->where('title', 'Checkout interruption')->firstOrFail();
        $this->assertSame($admin->id, $incident->created_by);
        $this->assertNull($incident->resolved_at);

        Carbon::setTestNow('2026-10-28 10:00:00');
        $this->put(route('dashboard.system-status.update', $incident), [
            'title' => 'Checkout interruption resolved',
            'description' => 'Customers can place orders normally again.',
            'type' => SystemIncident::TYPE_ISSUE,
            'status' => SystemIncident::STATUS_RESOLVED,
        ])->assertRedirect()->assertSessionHas('success');

        $incident->refresh();
        $this->assertSame(SystemIncident::STATUS_RESOLVED, $incident->status);
        $this->assertNotNull($incident->resolved_at);

        $this->get(route('system-status.index'))
            ->assertOk()
            ->assertSee('Operational')
            ->assertSee('Checkout interruption resolved');
    }

    public function test_city_manager_cannot_manage_system_status(): void
    {
        $manager = $this->user('manager@example.test', 'manager');

        $this->actingAs($manager)
            ->get(route('dashboard.system-status.index'))
            ->assertForbidden();
    }

    private function user(string $email, string $role): User
    {
        $user = User::query()->forceCreate([
            'name' => ucfirst($role).' User',
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
        $user->assignRole($role);

        return $user;
    }
}
