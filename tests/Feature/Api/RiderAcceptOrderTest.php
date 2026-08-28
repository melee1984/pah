<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderAcceptOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('order')) {
            Schema::create('order', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cart_id')->nullable();
                $table->unsignedBigInteger('status_id')->nullable();
                $table->unsignedBigInteger('order_status_id')->nullable();
                $table->unsignedBigInteger('booking_status_id')->nullable();
                $table->unsignedBigInteger('rider_id')->nullable();
                $table->unsignedBigInteger('accepted_by_rider_id')->nullable();
                $table->timestamp('store_accepted_at')->nullable();
                $table->timestamp('accepted_by_rider_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('order_process')) {
            Schema::create('order_process', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('status_id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('user_id');
                $table->timestamps();
            });
        }
    }

    public function test_rider_can_accept_an_available_order(): void
    {
        [$user, $riderId] = $this->createRider('accept-test@example.com');
        $orderId = $this->createAvailableOrder();
        $deliveryReference = $this->createDeliveryForOrder($orderId);

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/accept")
            ->assertOk()
            ->assertJsonPath('message', 'Order accepted.')
            ->assertJsonPath('order.rider_id', (string) $riderId)
            ->assertJsonPath('order.accepted_by_rider_id', (string) $riderId)
            ->assertJsonPath('order.accepted_at', fn ($value) => is_string($value) && $value !== '')
            ->assertJsonPath('delivery.id', $deliveryReference)
            ->assertJsonPath('delivery.state', 'accepted');

        $this->assertDatabaseHas('order', [
            'id' => $orderId,
            'rider_id' => $riderId,
            'accepted_by_rider_id' => $riderId,
        ]);
        $this->assertNotNull(DB::table('order')->where('id', $orderId)->value('accepted_at'));
        $this->assertDatabaseHas('rider_api_activity_logs', [
            'rider_id' => $riderId,
            'order_id' => $orderId,
            'type' => 'booking_accepted',
        ]);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/deliveries/active')
            ->assertOk()
            ->assertJsonPath('delivery.id', $deliveryReference);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/activity-logs?type=booking_accepted')
            ->assertOk()
            ->assertJsonPath('activity_logs.0.type', 'booking_accepted')
            ->assertJsonPath('activity_logs.0.order_id', (string) $orderId);
    }

    public function test_rider_can_list_and_filter_only_their_deliveries(): void
    {
        [$user, $riderId] = $this->createRider('delivery-list@example.com');
        [, $otherRiderId] = $this->createRider('delivery-list-other@example.com');

        $activeReference = $this->createDeliveryForOrder($this->createAvailableOrder(), $riderId, 'picked_up');
        $completedReference = $this->createDeliveryForOrder($this->createAvailableOrder(), $riderId, 'delivered');
        $this->createDeliveryForOrder($this->createAvailableOrder(), $otherRiderId, 'delivered');

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/deliveries')
            ->assertOk()
            ->assertJsonCount(2, 'deliveries')
            ->assertJsonFragment(['id' => $activeReference, 'state' => 'picked_up'])
            ->assertJsonFragment(['id' => $completedReference, 'state' => 'delivered'])
            ->assertJsonPath('next_cursor', null);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/deliveries?status=completed')
            ->assertOk()
            ->assertJsonCount(1, 'deliveries')
            ->assertJsonPath('deliveries.0.id', $completedReference);
    }

    public function test_delivery_list_validates_filters(): void
    {
        [$user] = $this->createRider('delivery-list-validation@example.com');
        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/deliveries?status=unknown')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_delivery_event_normalizes_iso_timestamp_for_mysql(): void
    {
        [$user, $riderId] = $this->createRider('delivery-event-time@example.com');
        $deliveryReference = $this->createDeliveryForOrder(
            $this->createAvailableOrder(),
            $riderId,
            'picked_up',
        );
        Sanctum::actingAs($user);

        $eventUrl = "/api/v1/rider/deliveries/{$deliveryReference}/events";

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson($eventUrl, [
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'going_to_customer',
                'occurred_at' => '2026-08-26T16:34:17.621396Z',
            ])
            ->assertCreated()
            ->assertJsonPath('current_state', 'going_to_customer');

        $this->assertDatabaseHas('rider_api_delivery_events', [
            'type' => 'going_to_customer',
            'occurred_at' => '2026-08-27 00:34:17',
        ]);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson($eventUrl, [
                'event_id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => 'going_to_customer',
                'occurred_at' => '2026-08-26T16:34:18.621396Z',
            ])
            ->assertOk()
            ->assertJsonPath('current_state', 'going_to_customer')
            ->assertJsonPath('idempotent_replay', true);

        $this->assertDatabaseCount('rider_api_delivery_events', 1);
    }

    public function test_rider_cannot_accept_an_order_assigned_to_another_rider(): void
    {
        [$user] = $this->createRider('conflict-test@example.com');
        [, $otherRiderId] = $this->createRider('other-rider@example.com');
        $orderId = $this->createAvailableOrder($otherRiderId);

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/accept")
            ->assertConflict()
            ->assertJsonPath('message', 'This order is assigned to another rider.');

        $this->assertDatabaseHas('order', [
            'id' => $orderId,
            'rider_id' => $otherRiderId,
            'accepted_by_rider_id' => null,
            'accepted_at' => null,
        ]);
    }

    public function test_declining_an_order_also_declines_the_rider_delivery_offer(): void
    {
        [$user, $riderId] = $this->createRider('decline-test@example.com');
        $orderId = $this->createAvailableOrder();
        $this->createDeliveryForOrder($orderId);
        $deliveryId = DB::table('rider_api_deliveries')
            ->where('legacy_order_id', $orderId)
            ->value('id');

        DB::table('rider_api_offers')->insert([
            'reference' => (string) \Illuminate\Support\Str::uuid(),
            'rider_id' => $riderId,
            'delivery_id' => $deliveryId,
            'status' => 'pending',
            'expires_at' => now()->addMinutes(15),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/decline")
            ->assertOk()
            ->assertJsonPath('message', 'Order declined.');

        $this->assertDatabaseHas('rider_decline_order', [
            'rider_id' => $riderId,
            'order_id' => $orderId,
        ]);
        $this->assertDatabaseHas('rider_api_offers', [
            'rider_id' => $riderId,
            'delivery_id' => $deliveryId,
            'status' => 'declined',
        ]);
    }

    public function test_rider_action_updates_the_status_and_records_the_app_payload(): void
    {
        [$user, $riderId] = $this->createRider('action-test@example.com');
        $orderId = DB::table('order')->insertGetId([
            'status_id' => 4,
            'order_status_id' => 4,
            'booking_status_id' => 4,
            'rider_id' => $riderId,
            'accepted_by_rider_id' => $riderId,
            'store_accepted_at' => now(),
            'accepted_at' => now(),
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deliveryReference = $this->createDeliveryForOrder($orderId, $riderId, 'arrived_at_merchant');
        $payload = [
            'action' => 'pickup-order',
            'latitude' => 14.5995,
            'longitude' => 120.9842,
            'device_timestamp' => '2026-08-19T22:05:00+08:00',
        ];

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/action", $payload)
            ->assertOk()
            ->assertJsonPath('message', 'Order action completed.')
            ->assertJsonPath('order.status_id', 5);

        $this->assertDatabaseHas('order', [
            'id' => $orderId,
            'order_status_id' => 5,
            'booking_status_id' => 5,
        ]);
        $this->assertDatabaseHas('rider_api_deliveries', [
            'legacy_order_id' => $orderId,
            'rider_id' => $riderId,
            'current_state' => 'picked_up',
        ]);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson("/api/v1/rider/deliveries/{$deliveryReference}/route")
            ->assertOk()
            ->assertJsonPath('leg', 'to_dropoff')
            ->assertJsonPath('destination.latitude', 14.6)
            ->assertJsonPath('destination.longitude', 120.99);
        $this->assertDatabaseHas('order_process', [
            'order_id' => $orderId,
            'status_id' => 5,
            'user_id' => $user->id,
        ]);

        $activity = DB::table('rider_api_activity_logs')
            ->where('rider_id', $riderId)
            ->where('order_id', $orderId)
            ->where('type', 'booking_action')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($payload, json_decode($activity->payload, true, 512, JSON_THROW_ON_ERROR));

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/activity-logs?type=booking_action')
            ->assertOk()
            ->assertJsonPath('activity_logs.0.payload.action', 'pickup-order')
            ->assertJsonPath('activity_logs.0.payload.device_timestamp', $payload['device_timestamp']);
    }

    public function test_arrival_event_updates_the_order_process_and_activity_log(): void
    {
        [$user, $riderId] = $this->createRider('arrival-event@example.com');
        $orderId = DB::table('order')->insertGetId([
            'status_id' => 5,
            'order_status_id' => 5,
            'booking_status_id' => 5,
            'rider_id' => $riderId,
            'accepted_by_rider_id' => $riderId,
            'store_accepted_at' => now(),
            'accepted_at' => now(),
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $deliveryReference = $this->createDeliveryForOrder($orderId, $riderId, 'going_to_customer');
        $payload = [
            'event_id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'arrived_at_customer',
            'occurred_at' => now()->toISOString(),
            'latitude' => 14.5995,
            'longitude' => 120.9842,
        ];

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/deliveries/{$deliveryReference}/events", $payload)
            ->assertCreated()
            ->assertJsonPath('current_state', 'arrived_at_customer');

        $this->assertDatabaseHas('order', [
            'id' => $orderId,
            'order_status_id' => 5,
            'booking_status_id' => 6,
        ]);
        $this->assertNotNull(DB::table('order')->where('id', $orderId)->value('delivered_at'));
        $this->assertDatabaseHas('order_process', [
            'order_id' => $orderId,
            'status_id' => 5,
            'user_id' => $user->id,
        ]);

        $activity = DB::table('rider_api_activity_logs')
            ->where('rider_id', $riderId)
            ->where('order_id', $orderId)
            ->where('type', 'booking_action')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame($payload, json_decode($activity->payload, true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array{User, int}
     */
    private function createRider(string $email): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Rider',
            'email' => $email,
            'password' => bcrypt('secret-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $riderId = DB::table('rider')->insertGetId([
            'name' => 'Test Rider',
            'active' => true,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [User::query()->findOrFail($userId), $riderId];
    }

    private function createAvailableOrder(?int $riderId = null): int
    {
        return DB::table('order')->insertGetId([
            'rider_id' => $riderId,
            'store_accepted_at' => now(),
            'booking_status_id' => 1,
            'order_status_id' => 3,
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createDeliveryForOrder(int $orderId, ?int $riderId = null, string $state = 'offered'): string
    {
        $reference = (string) \Illuminate\Support\Str::uuid();

        DB::table('rider_api_deliveries')->insert([
            'reference' => $reference,
            'rider_id' => $riderId,
            'legacy_order_id' => $orderId,
            'current_state' => $state,
            'merchant_name' => 'Test Merchant',
            'pickup_address' => 'Pickup address',
            'pickup_latitude' => 14.5,
            'pickup_longitude' => 120.9,
            'dropoff_address' => 'Customer address',
            'dropoff_latitude' => 14.6,
            'dropoff_longitude' => 120.99,
            'earnings_centavos' => 1000,
            'cod_centavos' => 0,
            'order_count' => 1,
            'is_batched' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $reference;
    }
}
