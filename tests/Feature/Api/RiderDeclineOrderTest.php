<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderDeclineOrderTest extends TestCase
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
                $table->unsignedBigInteger('rider_id')->nullable();
                $table->unsignedBigInteger('accepted_by_rider_id')->nullable();
                $table->timestamp('store_accepted_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_declined_order_is_hidden_from_the_riders_dashboard(): void
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Rider',
            'email' => 'decline-test@example.com',
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
        $orderId = DB::table('order')->insertGetId([
            'store_accepted_at' => now(),
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs(User::query()->findOrFail($userId));

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/decline")
            ->assertOk()
            ->assertJsonPath('message', 'Order declined.');

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/decline")
            ->assertOk();

        $this->assertDatabaseHas('rider_decline_order', [
            'rider_id' => $riderId,
            'order_id' => $orderId,
        ]);
        $this->assertDatabaseCount('rider_decline_order', 1);
        $this->assertDatabaseHas('rider_api_activity_logs', [
            'rider_id' => $riderId,
            'order_id' => $orderId,
            'type' => 'booking_declined',
        ]);
        $this->assertDatabaseCount('rider_api_activity_logs', 1);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/activity-logs?type=booking_declined')
            ->assertOk()
            ->assertJsonPath('activity_logs.0.type', 'booking_declined')
            ->assertJsonPath('activity_logs.0.order_id', (string) $orderId);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/dashboard')
            ->assertOk()
            ->assertJsonCount(0, 'bookings');
    }
}
