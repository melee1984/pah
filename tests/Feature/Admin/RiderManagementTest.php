<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\isAdmin;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RiderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_menu_page_lists_available_riders_and_balances(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Ana Rider', false, 125.50);

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->get(route('dashboard.rider'))
            ->assertOk()
            ->assertSee('Available riders')
            ->assertSee('Ana Rider')
            ->assertSee('₱125.50')
            ->assertSee(route('dashboard.riders.approve', $riderId))
            ->assertSee(route('dashboard.riders.credits', $riderId));
    }

    public function test_admin_can_approve_a_rider_and_the_approval_is_audited(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Ben Rider', false);

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->post(route('dashboard.riders.approve', $riderId))
            ->assertRedirect()
            ->assertSessionHas('success');

        $rider = DB::table('rider')->find($riderId);
        $this->assertSame(1, (int) $rider->active);
        $this->assertSame(1, (int) $rider->is_active);
        $this->assertNotNull($rider->approved_at);

        $log = DB::table('rider_api_activity_logs')
            ->where('rider_id', $riderId)
            ->where('type', 'admin_approval')
            ->first();
        $this->assertNotNull($log);
        $this->assertSame($admin->id, json_decode($log->payload, true)['performed_by_user_id']);
    }

    public function test_each_credit_adjustment_updates_the_balance_and_creates_an_admin_audit_transaction(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Cara Rider', true, 50);

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->post(route('dashboard.riders.credits', $riderId), [
                'action' => 'add',
                'amount' => '25.50',
                'reason' => 'Weekly rider top-up',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->post(route('dashboard.riders.credits', $riderId), [
                'action' => 'deduct',
                'amount' => '10.25',
                'reason' => 'Correction of duplicate top-up',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(
            65.25,
            (float) DB::table('rider_api_wallets')->where('rider_id', $riderId)->value('credit_amount'),
        );
        $this->assertDatabaseHas('rider_api_wallet_transactions', [
            'rider_id' => $riderId,
            'type' => 'admin_credit_adjustment',
            'amount_centavos' => 2550,
            'balance_after_centavos' => 7550,
            'description' => 'Weekly rider top-up',
            'performed_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('rider_api_wallet_transactions', [
            'rider_id' => $riderId,
            'type' => 'admin_credit_adjustment',
            'amount_centavos' => -1025,
            'balance_after_centavos' => 6525,
            'description' => 'Correction of duplicate top-up',
            'performed_by_user_id' => $admin->id,
        ]);
        $this->assertSame(2, DB::table('rider_api_wallet_transactions')->where('rider_id', $riderId)->count());
    }

    public function test_credit_deduction_cannot_make_the_balance_negative(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Dan Rider', true, 5);

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->from(route('dashboard.rider'))
            ->post(route('dashboard.riders.credits', $riderId), [
                'action' => 'deduct',
                'amount' => '5.01',
                'reason' => 'Invalid deduction',
            ])
            ->assertRedirect(route('dashboard.rider'))
            ->assertSessionHasErrors('amount');

        $this->assertEquals(
            5,
            (float) DB::table('rider_api_wallets')->where('rider_id', $riderId)->value('credit_amount'),
        );
        $this->assertDatabaseCount('rider_api_wallet_transactions', 0);
    }

    private function createAdmin(): User
    {
        return User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ]);
    }

    private function createRider(string $name, bool $approved, float $credits = 0): int
    {
        $now = now();
        $riderId = DB::table('rider')->insertGetId([
            'name' => $name,
            'mobile' => '09171234567',
            'active' => $approved,
            'is_active' => $approved,
            'approved_at' => $approved ? $now : null,
            'date_join' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('rider_api_wallets')->insert([
            'rider_id' => $riderId,
            'credit_amount' => $credits,
            'credit_points' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $riderId;
    }
}
