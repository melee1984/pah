<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\isAdmin;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiderCommissionReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_rider_commissions_by_rider_and_date_range(): void
    {
        $admin = User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => 'rider-report-admin@example.com',
            'password' => Hash::make('password'),
        ]);
        $firstRider = $this->createRider('Ana Rider', 75);
        $secondRider = $this->createRider('Ben Rider', 50);

        $this->createCommission($firstRider, 10_000, 2_000, '2026-09-03 09:30:00');
        $this->createCommission($firstRider, 5_000, 1_000, '2026-08-20 09:30:00');
        $this->createCommission($secondRider, 8_000, 1_600, '2026-09-03 10:30:00');

        $this->withoutMiddleware(isAdmin::class)
            ->actingAs($admin)
            ->get(route('dashboard.report.riders', [
                'rider_id' => $firstRider,
                'from' => '2026-09-01',
                'to' => '2026-09-30',
            ]))
            ->assertOk()
            ->assertSee('Rider Commission Report')
            ->assertSee('Ana Rider')
            ->assertSee('₱20.00')
            ->assertSee('₱75.00')
            ->assertDontSee('₱16.00');
    }

    public function test_report_rejects_an_invalid_date_range(): void
    {
        $this->withoutMiddleware(isAdmin::class)
            ->get(route('dashboard.report.riders', [
                'from' => '2026-09-30',
                'to' => '2026-09-01',
            ]))
            ->assertSessionHasErrors('to');
    }

    private function createRider(string $name, float $walletBalance): int
    {
        $riderId = DB::table('rider')->insertGetId([
            'name' => $name,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rider_api_wallets')->insert([
            'rider_id' => $riderId,
            'credit_amount' => $walletBalance,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $riderId;
    }

    private function createCommission(int $riderId, int $earningsCentavos, int $commissionCentavos, string $occurredAt): void
    {
        $deliveryReference = (string) Str::uuid();
        DB::table('rider_api_deliveries')->insert([
            'reference' => $deliveryReference,
            'rider_id' => $riderId,
            'current_state' => 'delivered',
            'merchant_name' => 'Test merchant',
            'earnings_centavos' => $earningsCentavos,
            'commission_percentage' => 20,
            'commission_centavos' => $commissionCentavos,
            'cod_centavos' => 0,
            'order_count' => 1,
            'is_batched' => false,
            'completed_at' => $occurredAt,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
        DB::table('rider_api_wallet_transactions')->insert([
            'reference' => (string) Str::uuid(),
            'rider_id' => $riderId,
            'type' => 'pahatud_commission',
            'amount_centavos' => -$commissionCentavos,
            'balance_after_centavos' => 5_000,
            'description' => 'Pahatud delivery commission',
            'related_type' => 'delivery',
            'related_reference' => $deliveryReference,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
            'updated_at' => $occurredAt,
        ]);
    }
}
