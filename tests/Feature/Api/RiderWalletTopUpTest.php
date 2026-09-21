<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class RiderWalletTopUpTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_rider_can_submit_and_read_a_pending_wallet_top_up(): void
    {
        Storage::fake('local');
        [$token, $riderId] = $this->loginApprovedRider();

        $response = $this->withHeaders($this->riderHeaders($token))->post('/api/v1/rider/wallet/top-ups', [
            'amount_centavos' => 25000,
            'payment_method' => 'gcash',
            'payment_reference' => 'GCASH-123456',
            'proof' => UploadedFile::fake()->image('gcash-receipt.jpg'),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Wallet top-up submitted for review.')
            ->assertJsonPath('top_up.amount_centavos', 25000)
            ->assertJsonPath('top_up.payment_method', 'gcash')
            ->assertJsonPath('top_up.status', 'pending');

        $topUpId = $response->json('top_up.id');
        $this->withHeaders($this->riderHeaders($token))
            ->getJson('/api/v1/rider/wallet/top-ups')
            ->assertOk()
            ->assertJsonPath('top_ups.0.id', $topUpId);
        $this->withHeaders($this->riderHeaders($token))
            ->getJson("/api/v1/rider/wallet/top-ups/{$topUpId}")
            ->assertOk()
            ->assertJsonPath('top_up.payment_reference', 'GCASH-123456');

        $this->assertDatabaseHas('rider_api_wallet_top_ups', [
            'reference' => $topUpId,
            'rider_id' => $riderId,
            'amount_centavos' => 25000,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('rider_api_activity_logs', [
            'rider_id' => $riderId,
            'type' => 'top_up_submitted',
        ]);
        $this->assertDatabaseCount('rider_api_wallet_transactions', 0);
        $this->assertEquals(
            0,
            (float) DB::table('rider_api_wallets')->where('rider_id', $riderId)->value('credit_amount'),
        );
        Storage::disk('local')->assertExists(
            DB::table('rider_api_wallet_top_ups')->where('reference', $topUpId)->value('proof_path'),
        );
    }

    public function test_rider_cannot_reuse_a_payment_reference(): void
    {
        Storage::fake('local');
        [$token] = $this->loginApprovedRider();
        $payload = [
            'amount_centavos' => 10000,
            'payment_method' => 'bank_transfer',
            'payment_reference' => 'BANK-ABC-100',
            'proof' => UploadedFile::fake()->image('receipt-one.jpg'),
        ];

        $this->withHeaders($this->riderHeaders($token))
            ->post('/api/v1/rider/wallet/top-ups', $payload)
            ->assertCreated();

        $payload['proof'] = UploadedFile::fake()->image('receipt-two.jpg');
        $this->withHeaders($this->riderHeaders($token))
            ->post('/api/v1/rider/wallet/top-ups', $payload)
            ->assertConflict();

        $this->assertDatabaseCount('rider_api_wallet_top_ups', 1);
    }

    /**
     * @return array{string, int}
     */
    private function loginApprovedRider(): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Top Up Rider',
            'email' => 'top-up-rider@example.com',
            'password' => Hash::make('secret-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $riderId = DB::table('rider')->insertGetId([
            'name' => 'Top Up Rider',
            'date_join' => now(),
            'mobile' => '09171234567',
            'active' => true,
            'is_active' => true,
            'approved_at' => now(),
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rider_api_wallets')->insert([
            'rider_id' => $riderId,
            'credit_amount' => 0,
            'credit_points' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $token = $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/v1/rider/auth/login', [
                'email' => 'top-up-rider@example.com',
                'password' => 'secret-password',
                'device_id' => (string) Str::uuid(),
                'platform' => 'android',
            ])
            ->assertOk()
            ->json('access_token');

        return [$token, $riderId];
    }

    /**
     * @return array<string, string>
     */
    private function riderHeaders(string $token): array
    {
        return [
            'X-Admin-Request' => 'apiRequestHandle001',
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
        ];
    }
}
