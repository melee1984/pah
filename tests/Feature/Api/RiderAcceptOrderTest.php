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
                $table->unsignedBigInteger('rider_id')->nullable();
                $table->unsignedBigInteger('accepted_by_rider_id')->nullable();
                $table->timestamp('store_accepted_at')->nullable();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_rider_can_accept_an_available_order(): void
    {
        [$user, $riderId] = $this->createRider('accept-test@example.com');
        $orderId = $this->createAvailableOrder();

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson("/api/v1/rider/orders/{$orderId}/accept")
            ->assertOk()
            ->assertJsonPath('message', 'Order accepted.')
            ->assertJsonPath('order.rider_id', (string) $riderId)
            ->assertJsonPath('order.accepted_by_rider_id', (string) $riderId)
            ->assertJsonPath('order.accepted_at', fn ($value) => is_string($value) && $value !== '');

        $this->assertDatabaseHas('order', [
            'id' => $orderId,
            'rider_id' => $riderId,
            'accepted_by_rider_id' => $riderId,
        ]);
        $this->assertNotNull(DB::table('order')->where('id', $orderId)->value('accepted_at'));
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
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
