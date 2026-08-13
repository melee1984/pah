<?php

namespace Tests\Feature\Api;

use App\RiderApplication;
use App\Services\RiderOfferDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class FullRiderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_rider_application_accepts_a_seven_character_password(): void
    {
        $this->api()
            ->postJson('/api/v1/rider/applications', [
                'email' => 'seven-character@example.com',
                'password' => 'letmein',
                'password_confirmation' => 'letmein',
            ])
            ->assertCreated()
            ->assertJsonPath('application.status', 'draft');
    }

    public function test_login_rejects_a_pending_rider_application(): void
    {
        $this->createUser('pending@example.com');
        RiderApplication::create([
            'reference' => (string) Str::uuid(),
            'email' => 'pending@example.com',
            'password' => 'secret-password',
            'status' => RiderApplication::STATUS_PENDING,
        ]);

        $this->api()
            ->postJson('/api/v1/rider/auth/login', [
                'email' => 'pending@example.com',
                'password' => 'secret-password',
            ])
            ->assertForbidden()
            ->assertJsonPath('account_status', 'pending')
            ->assertJsonPath('capabilities.can_go_online', false);
    }

    public function test_an_approved_rider_can_authenticate_and_use_core_operations(): void
    {
        $token = $this->loginApprovedRider();

        $this->authenticated($token)
            ->getJson('/api/v1/rider/me')
            ->assertOk()
            ->assertJsonPath('account_status', 'approved')
            ->assertJsonPath('capabilities.can_accept_offers', true);

        $this->authenticated($token)
            ->putJson('/api/v1/rider/availability', ['state' => 'available'])
            ->assertOk()
            ->assertJsonPath('availability.state', 'available');

        $this->authenticated($token)
            ->postJson('/api/v1/rider/location', [
                'delivery_id' => null,
                'latitude' => 10.3157,
                'longitude' => 123.8854,
                'accuracy_meters' => 12,
                'heading' => 95,
                'speed_mps' => 8.4,
                'recorded_at' => now()->toISOString(),
            ])
            ->assertAccepted();

        $this->authenticated($token)
            ->getJson('/api/v1/rider/dashboard')
            ->assertOk()
            ->assertJsonPath('wallet.available_centavos', 0);

        $this->authenticated($token)
            ->getJson('/api/v1/rider/wallet')
            ->assertOk()
            ->assertJsonPath('wallet.amount_owed_centavos', 0);

        $this->assertDatabaseCount('rider_api_locations', 1);
    }

    public function test_offer_acceptance_and_delivery_events_are_authorized_sequential_and_idempotent(): void
    {
        $token = $this->loginApprovedRider();
        $riderId = DB::table('rider')->value('id');
        $deliveryReference = (string) Str::uuid();
        $offerReference = (string) Str::uuid();
        $deliveryId = DB::table('rider_api_deliveries')->insertGetId([
            'reference' => $deliveryReference,
            'current_state' => 'offered',
            'merchant_name' => 'Pahatud Test Store',
            'pickup_area' => 'Lahug',
            'pickup_address' => 'Lahug, Cebu City',
            'dropoff_area' => 'Mabolo',
            'dropoff_address' => 'Mabolo, Cebu City',
            'earnings_centavos' => 8500,
            'cod_centavos' => 12000,
            'order_count' => 1,
            'is_batched' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('rider_api_offers')->insert([
            'reference' => $offerReference,
            'rider_id' => $riderId,
            'delivery_id' => $deliveryId,
            'status' => 'pending',
            'expires_at' => now()->addMinute(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authenticated($token)
            ->getJson('/api/v1/rider/offers/current')
            ->assertOk()
            ->assertJsonPath('offer.id', $offerReference)
            ->assertJsonMissingPath('offer.dropoff_address');

        $this->authenticated($token)
            ->postJson("/api/v1/rider/offers/{$offerReference}/accept")
            ->assertOk()
            ->assertJsonPath('delivery.id', $deliveryReference)
            ->assertJsonPath('delivery.state', 'accepted');

        $eventId = (string) Str::uuid();
        $event = [
            'event_id' => $eventId,
            'type' => 'going_to_merchant',
            'occurred_at' => now()->toISOString(),
            'latitude' => 10.3157,
            'longitude' => 123.8854,
        ];

        $this->authenticated($token)
            ->postJson("/api/v1/rider/deliveries/{$deliveryReference}/events", $event)
            ->assertCreated()
            ->assertJsonPath('current_state', 'going_to_merchant');

        $this->authenticated($token)
            ->postJson("/api/v1/rider/deliveries/{$deliveryReference}/events", $event)
            ->assertOk()
            ->assertJsonPath('idempotent_replay', true);

        $this->authenticated($token)
            ->postJson("/api/v1/rider/deliveries/{$deliveryReference}/events", [
                'event_id' => (string) Str::uuid(),
                'type' => 'delivered',
                'occurred_at' => now()->toISOString(),
            ])
            ->assertConflict()
            ->assertJsonPath('current_state', 'going_to_merchant');

        $this->assertDatabaseCount('rider_api_delivery_events', 2);
    }

    public function test_wallet_messaging_notifications_and_settings_endpoints_are_operational(): void
    {
        $token = $this->loginApprovedRider();
        $riderId = DB::table('rider')->value('id');
        DB::table('rider_api_wallets')->insert([
            'rider_id' => $riderId,
            'available_centavos' => 20000,
            'pending_centavos' => 0,
            'cash_collected_centavos' => 0,
            'amount_owed_centavos' => 0,
            'daily_cod_limit_centavos' => 100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $account = $this->authenticated($token)
            ->postJson('/api/v1/rider/wallet/payout-accounts', [
                'method' => 'GCash',
                'account_name' => 'Carlo Juan',
                'account_number' => '09171234567',
            ])
            ->assertCreated()
            ->assertJsonPath('payout_account.masked_account_number', '•••••••4567')
            ->json('payout_account.id');

        $this->authenticated($token)
            ->postJson('/api/v1/rider/wallet/withdrawals', [
                'payout_account_id' => $account,
                'amount_centavos' => 10000,
            ])
            ->assertCreated()
            ->assertJsonPath('withdrawal.status', 'pending');

        $this->authenticated($token)
            ->postJson('/api/v1/rider/conversations', [
                'type' => 'support',
                'subject' => 'Wallet question',
                'message' => 'Please verify my withdrawal.',
                'client_message_id' => (string) Str::uuid(),
            ])
            ->assertCreated()
            ->assertJsonPath('conversation.type', 'support');

        $this->authenticated($token)
            ->putJson('/api/v1/rider/notification-preferences', [
                'marketing' => true,
            ])
            ->assertOk()
            ->assertJsonPath('preferences.marketing', true);

        $this->authenticated($token)
            ->putJson('/api/v1/rider/settings', [
                'language' => 'ceb',
                'navigation_app' => 'google_maps',
            ])
            ->assertOk()
            ->assertJsonPath('settings.language', 'ceb');

        $this->authenticated($token)
            ->getJson('/api/v1/rider/profile/performance')
            ->assertOk()
            ->assertJsonPath('performance.completed_deliveries', 0);
    }

    public function test_available_riders_receive_merchant_orders_and_only_one_can_accept(): void
    {
        $firstToken = $this->loginApprovedRider('first-rider@example.com');
        $firstRiderId = DB::table('rider')->latest('id')->value('id');
        $this->loginApprovedRider('second-rider@example.com');
        $secondRiderId = DB::table('rider')->latest('id')->value('id');
        $deliveryReference = (string) Str::uuid();
        $deliveryId = DB::table('rider_api_deliveries')->insertGetId([
            'reference' => $deliveryReference,
            'current_state' => 'offered',
            'merchant_name' => 'Pahatud Test Store',
            'pickup_area' => 'Lahug',
            'dropoff_area' => 'Mabolo',
            'earnings_centavos' => 8500,
            'cod_centavos' => 0,
            'order_count' => 1,
            'is_batched' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authenticated($firstToken)
            ->putJson('/api/v1/rider/availability', ['state' => 'available'])
            ->assertOk();
        app(RiderOfferDispatcher::class)->dispatchPendingForRider($secondRiderId);

        $firstOffer = DB::table('rider_api_offers')
            ->where('rider_id', $firstRiderId)
            ->where('delivery_id', $deliveryId)
            ->value('reference');
        $secondOffer = DB::table('rider_api_offers')
            ->where('rider_id', $secondRiderId)
            ->where('delivery_id', $deliveryId)
            ->value('reference');
        $this->assertNotNull($firstOffer);
        $this->assertNotNull($secondOffer);

        $this->authenticated($firstToken)
            ->postJson("/api/v1/rider/offers/{$firstOffer}/accept")
            ->assertOk()
            ->assertJsonPath('delivery.id', $deliveryReference);
        $this->assertDatabaseHas('rider_api_deliveries', [
            'id' => $deliveryId,
            'rider_id' => $firstRiderId,
            'current_state' => 'accepted',
        ]);
        $this->assertDatabaseHas('rider_api_offers', [
            'reference' => $secondOffer,
            'status' => 'expired',
        ]);
    }

    private function loginApprovedRider(string $email = 'rider@example.com'): string
    {
        $userId = $this->createUser($email);
        DB::table('rider')->insert([
            'name' => 'Carlo Juan',
            'date_join' => now(),
            'mobile' => '09171234567',
            'active' => true,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->api()
            ->postJson('/api/v1/rider/auth/login', [
                'email' => $email,
                'password' => 'secret-password',
                'device_id' => (string) Str::uuid(),
                'device_name' => 'Test phone',
                'platform' => 'android',
                'app_version' => '1.0.0',
            ])
            ->assertOk()
            ->assertJsonPath('account_status', 'approved')
            ->json('access_token');
    }

    private function createUser(string $email): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'Carlo Juan',
            'email' => $email,
            'password' => Hash::make('secret-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function api(): static
    {
        return $this->withHeader('X-Admin-Request', 'apiRequestHandle001');
    }

    private function authenticated(string $token): static
    {
        return $this->withHeaders([
            'X-Admin-Request' => 'apiRequestHandle001',
            'Authorization' => "Bearer {$token}",
        ]);
    }
}
