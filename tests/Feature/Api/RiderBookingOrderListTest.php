<?php

namespace Tests\Feature\Api;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderBookingOrderListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

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

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->unsignedBigInteger('address_id')->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->unsignedBigInteger('partner_location_address_id')->nullable();
            $table->string('order_no')->nullable();
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->timestamps();
        });

        Schema::create('cart_user_address', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->timestamps();
        });

        foreach (['payment_method', 'partner_location', 'partners'] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->timestamps();
            });
        }

        Schema::create('library_status', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('sorting')->default(0);
            $table->string('description')->nullable();
        });

        Schema::create('order_process', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('rider_id')->nullable();
            $table->unsignedBigInteger('accepted_by_rider_id')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->date('booking_date');
            $table->time('booking_time');
            $table->decimal('delivery_rate', 10, 2)->default(0);
            $table->string('job_order');
            $table->unsignedBigInteger('pickup_id')->nullable();
            $table->unsignedBigInteger('dropoff_id')->nullable();
            $table->timestamps();
        });

        Schema::create('library_booking_status', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('sorting')->default(0);
            $table->string('description')->nullable();
        });

        Schema::create('booking_order_process', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('booking_id');
            $table->unsignedBigInteger('status_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });

        Schema::create('booking_pickup_info', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('booking_drop_info', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        DB::table('library_booking_status')->insert([
            ['id' => 1, 'title' => 'Booking Placed', 'sorting' => 1],
            ['id' => 2, 'title' => 'Booking Accepted', 'sorting' => 2],
            ['id' => 4, 'title' => 'Item Pickup', 'sorting' => 4],
            ['id' => 6, 'title' => 'Delivered', 'sorting' => 6],
            ['id' => 7, 'title' => 'Cancelled', 'sorting' => 7],
        ]);

        DB::table('library_status')->insert([
            ['id' => 1, 'title' => 'Order Placed', 'sorting' => 1],
            ['id' => 7, 'title' => 'Delivered', 'sorting' => 7],
            ['id' => 8, 'title' => 'Cancelled', 'sorting' => 8],
        ]);
    }

    public function test_combined_endpoint_filters_bookings_by_requested_status(): void
    {
        [$user, $riderId] = $this->createRider();
        $bookingIds = [
            'new' => $this->createBooking($riderId, 1),
            'accepted' => $this->createBooking($riderId, 2, now()),
            'completed' => $this->createBooking($riderId, 6, now()),
            'cancelled' => $this->createBooking($riderId, 7, now()),
            'failed' => $this->createBooking($riderId, 4, now()),
        ];

        DB::table('rider_api_deliveries')->insert([
            'reference' => (string) Str::uuid(),
            'rider_id' => $riderId,
            'legacy_booking_id' => $bookingIds['failed'],
            'current_state' => 'failed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        foreach ($bookingIds as $status => $bookingId) {
            $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
                ->getJson("/api/v1/rider/bookings-orders?status={$status}")
                ->assertOk()
                ->assertJsonCount(1, 'orders')
                ->assertJsonPath('orders.0.id', $bookingId)
                ->assertJsonPath('orders.0.type', 'booking');
        }
    }

    public function test_combined_endpoint_rejects_an_unknown_status(): void
    {
        [$user] = $this->createRider();

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/bookings-orders?status=pending')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_combined_endpoint_returns_marketplace_orders_too(): void
    {
        [$user, $riderId] = $this->createRider();
        $cartId = DB::table('cart')->insertGetId([
            'order_no' => 'ORDER-1001',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $orderId = DB::table('order')->insertGetId([
            'cart_id' => $cartId,
            'status_id' => 1,
            'rider_id' => $riderId,
            'store_accepted_at' => now(),
            'submitted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson('/api/v1/rider/bookings-orders?status=new')
            ->assertOk()
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.id', $orderId)
            ->assertJsonPath('orders.0.type', 'order');
    }

    /**
     * @return array{User, int}
     */
    private function createRider(): array
    {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Status Filter Rider',
            'email' => 'status-filter@example.com',
            'password' => bcrypt('secret-password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $riderId = DB::table('rider')->insertGetId([
            'name' => 'Status Filter Rider',
            'active' => true,
            'user_id' => $userId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [User::query()->findOrFail($userId), $riderId];
    }

    private function createBooking(int $riderId, int $statusId, $acceptedAt = null): int
    {
        return DB::table('bookings')->insertGetId([
            'status_id' => $statusId,
            'rider_id' => $riderId,
            'accepted_by_rider_id' => $acceptedAt ? $riderId : null,
            'accepted_at' => $acceptedAt,
            'booking_date' => now()->toDateString(),
            'booking_time' => now()->format('H:i:s'),
            'delivery_rate' => 100,
            'job_order' => (string) Str::random(8),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
