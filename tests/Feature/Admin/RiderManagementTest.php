<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\isAdmin;
use App\Mail\RiderApplicationApprovedMail;
use App\Mail\RiderApplicationDeclinedMail;
use App\RiderApplication;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiderManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_approving_an_application_emails_the_rider_once(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $application = RiderApplication::query()->forceCreate([
            'reference' => (string) Str::uuid(),
            'full_name' => 'Ana Rider',
            'email' => 'ana@example.com',
            'mobile' => '09171234567',
            'password' => Hash::make('password'),
            'status' => RiderApplication::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $approve = fn () => $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->post(route('dashboard.rider-applications.approve', $application));

        $approve()->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('rider_applications', [
            'id' => $application->id,
            'status' => RiderApplication::STATUS_APPROVED,
        ]);
        Mail::assertSent(RiderApplicationApprovedMail::class, function (RiderApplicationApprovedMail $mail) use ($application) {
            return $mail->hasTo($application->email)
                && str_contains($mail->render(), 'follow the account activation steps');
        });

        $approve()->assertRedirect()->assertSessionHasErrors('application');
        Mail::assertSentCount(1);
    }

    public function test_rider_menu_page_lists_available_riders_and_balances(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Ana Rider', false, 125.50);

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->get(route('dashboard.rider'))
            ->assertOk()
            ->assertSee('Rider management')
            ->assertSee('Ana Rider')
            ->assertSee('₱125.50')
            ->assertSee(route('dashboard.riders.approve', $riderId))
            ->assertSee(route('dashboard.riders.credits', $riderId));
    }

    public function test_available_riders_page_includes_ready_riders_without_push_devices(): void
    {
        $admin = $this->createAdmin();
        $readyRiderId = $this->createRider('Ready Rider', true);
        $missingDeviceRiderId = $this->createRider('No Device Rider', true);
        $this->createRider('Offline Rider', true);
        $now = now();

        DB::table('rider_api_availability')->insert([
            [
                'rider_id' => $readyRiderId,
                'state' => 'available',
                'heartbeat_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'rider_id' => $missingDeviceRiderId,
                'state' => 'available',
                'heartbeat_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        DB::table('rider_api_locations')->insert([
            [
                'rider_id' => $readyRiderId,
                'latitude' => 7.0731,
                'longitude' => 125.6128,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'rider_id' => $missingDeviceRiderId,
                'latitude' => 7.0731,
                'longitude' => 125.6128,
                'recorded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        DB::table('rider_api_devices')->insert([
            'reference' => (string) Str::uuid(),
            'rider_id' => $readyRiderId,
            'device_key' => 'ready-rider-phone',
            'push_token' => 'push-token',
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->get(route('dashboard.riders.available'))
            ->assertOk()
            ->assertSee('Ready for booking')
            ->assertSee('Ready Rider')
            ->assertSee('No Device Rider')
            ->assertSee('No push device')
            ->assertDontSee('Offline Rider')
            ->assertViewHas('metrics', [
                'ready' => 2,
                'available' => 2,
                'busy' => 0,
                'unavailable' => 1,
            ]);
    }

    public function test_admin_can_decline_a_pending_application_with_a_reason(): void
    {
        Mail::fake();
        $admin = $this->createAdmin();
        $applicant = User::query()->forceCreate([
            'name' => 'Declined Rider',
            'email' => 'declined@example.com',
            'password' => Hash::make('password'),
        ]);
        $riderId = DB::table('rider')->insertGetId([
            'user_id' => $applicant->id,
            'name' => 'Declined Rider',
            'mobile' => '09171234567',
            'active' => false,
            'is_active' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $application = RiderApplication::query()->forceCreate([
            'reference' => (string) Str::uuid(),
            'full_name' => 'Declined Rider',
            'email' => 'declined@example.com',
            'mobile' => '09171234567',
            'password' => Hash::make('password'),
            'status' => RiderApplication::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->withoutMiddleware(isAdmin::class)->actingAs($admin)
            ->post(route('dashboard.rider-applications.decline', $application), ['reason' => 'Documents could not be verified.'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('rider_applications', [
            'id' => $application->id,
            'status' => RiderApplication::STATUS_REJECTED,
            'review_notes' => 'Documents could not be verified.',
        ]);
        $this->assertNotNull(DB::table('rider')->where('id', $riderId)->value('archived_at'));
        Mail::assertSent(RiderApplicationDeclinedMail::class, function (RiderApplicationDeclinedMail $mail) use ($application) {
            return $mail->hasTo($application->email)
                && str_contains($mail->render(), 'Documents could not be verified.');
        });

        $this->withoutMiddleware(isAdmin::class)->actingAs($admin)
            ->post(route('dashboard.rider-applications.decline', $application), ['reason' => 'Again'])
            ->assertSessionHasErrors('application');
        Mail::assertSentCount(1);
    }

    public function test_admin_can_archive_and_restore_a_rider_without_changing_approval(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Archived Rider', true);

        $this->withoutMiddleware(isAdmin::class)->actingAs($admin)
            ->post(route('dashboard.riders.archive', $riderId))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(DB::table('rider')->where('id', $riderId)->value('archived_at'));
        $this->assertSame(1, (int) DB::table('rider')->where('id', $riderId)->value('active'));

        $this->withoutMiddleware(isAdmin::class)->actingAs($admin)
            ->get(route('dashboard.rider'))
            ->assertOk()
            ->assertViewHas('riders', fn ($riders) => ! $riders->getCollection()->contains('id', $riderId));

        $this->withoutMiddleware(isAdmin::class)->actingAs($admin)
            ->get(route('dashboard.rider', ['view' => 'archived']))
            ->assertOk()
            ->assertViewHas('riders', fn ($riders) => $riders->getCollection()->contains('id', $riderId));

        $this->withoutMiddleware(isAdmin::class)->actingAs($admin)
            ->post(route('dashboard.riders.restore', $riderId))
            ->assertRedirect();

        $this->assertNull(DB::table('rider')->where('id', $riderId)->value('archived_at'));
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

    public function test_admin_approval_of_a_top_up_credits_the_wallet_once_and_logs_the_transaction(): void
    {
        $admin = $this->createAdmin();
        $riderId = $this->createRider('Ella Rider', true, 20);
        $topUpReference = (string) Str::uuid();
        DB::table('rider_api_wallet_top_ups')->insert([
            'reference' => $topUpReference,
            'rider_id' => $riderId,
            'amount_centavos' => 3000,
            'payment_method' => 'gcash',
            'payment_reference' => 'GCASH-ELLA-1',
            'proof_path' => 'rider-wallet-top-ups/test/proof.jpg',
            'proof_original_name' => 'proof.jpg',
            'proof_mime_type' => 'image/jpeg',
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $approve = fn () => $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->post(route('dashboard.rider-top-ups.approve', $topUpReference));

        $approve()->assertRedirect()->assertSessionHas('success');
        $approve()->assertRedirect()->assertSessionHas('success');

        $this->assertEquals(
            50,
            (float) DB::table('rider_api_wallets')->where('rider_id', $riderId)->value('credit_amount'),
        );
        $this->assertDatabaseHas('rider_api_wallet_top_ups', [
            'reference' => $topUpReference,
            'status' => 'approved',
            'reviewed_by_user_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('rider_api_wallet_transactions', [
            'rider_id' => $riderId,
            'type' => 'wallet_top_up',
            'amount_centavos' => 3000,
            'balance_after_centavos' => 5000,
            'related_reference' => $topUpReference,
            'performed_by_user_id' => $admin->id,
        ]);
        $this->assertSame(1, DB::table('rider_api_wallet_transactions')->where('related_reference', $topUpReference)->count());
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
