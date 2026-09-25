<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\RewardController;
use App\LibraryStatus;
use App\Model\Orders\Orders;
use App\Services\UserRewardService;
use App\User;
use App\UserReward;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class UserRewardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'checkout.convenience_fee_rate' => 0,
            'checkout.vat_rate' => 0,
            'rewards.enabled' => true,
            'rewards.php_per_point' => 100,
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedInteger('qty');
            $table->decimal('price', 12, 2);
            $table->decimal('variance_total', 12, 2)->default(0);
            $table->decimal('price_comm_total', 12, 2)->default(0);
            $table->decimal('variance_total_comm_total', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedInteger('order_status_id');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedInteger('points');
            $table->decimal('order_amount', 12, 2);
            $table->decimal('php_per_point', 12, 2);
            $table->string('status', 20)->index();
            $table->timestamp('earned_at');
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([['id' => 5], ['id' => 6]]);
        DB::table('cart')->insert(['id' => 10, 'delivery_fee' => 20, 'discount_amount' => 10]);
        DB::table('cart_details')->insert([
            'cart_id' => 10,
            'qty' => 2,
            'price' => 120,
            'variance_total' => 0,
            'price_comm_total' => 0,
            'variance_total_comm_total' => 0,
            'discount_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('order')->insert([
            'id' => 20,
            'user_id' => 5,
            'cart_id' => 10,
            'order_status_id' => LibraryStatus::STATUS_DELIVERED,
            'delivered_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_delivered_order_earns_whole_points_once(): void
    {
        $order = Orders::query()->findOrFail(20);
        $service = new UserRewardService;

        $service->sync($order);
        $service->sync($order);

        $this->assertDatabaseCount('user_rewards', 1);
        $this->assertDatabaseHas('user_rewards', [
            'user_id' => 5,
            'order_id' => 20,
            'points' => 2,
            'order_amount' => 250,
            'php_per_point' => 100,
            'status' => UserReward::STATUS_EARNED,
        ]);
    }

    public function test_cancelled_order_reward_is_reversed(): void
    {
        $service = new UserRewardService;
        $service->sync(Orders::query()->findOrFail(20));
        DB::table('order')->where('id', 20)->update([
            'order_status_id' => LibraryStatus::STATUS_CANCELLED,
            'delivered_at' => null,
        ]);

        $service->sync(Orders::query()->findOrFail(20));

        $this->assertDatabaseHas('user_rewards', [
            'order_id' => 20,
            'status' => UserReward::STATUS_REVERSED,
        ]);
        $this->assertNotNull(UserReward::query()->firstOrFail()->reversed_at);
    }

    public function test_endpoint_returns_only_authenticated_users_available_points(): void
    {
        (new UserRewardService)->sync(Orders::query()->findOrFail(20));
        UserReward::query()->create([
            'user_id' => 6,
            'order_id' => 21,
            'points' => 99,
            'order_amount' => 9900,
            'php_per_point' => 100,
            'status' => UserReward::STATUS_EARNED,
            'earned_at' => now(),
        ]);

        $request = Request::create('/api/mobile/rewards', 'GET');
        $request->setUserResolver(fn ($guard = null) => User::query()->findOrFail(5));
        $data = (new RewardController)->index($request)->getData(true);

        $this->assertSame(1, $data['status']);
        $this->assertSame(2, $data['data']['points']);
        $this->assertSame(100, $data['data']['php_per_point']);
        $this->assertCount(1, $data['data']['rewards']);
        $this->assertSame(20, $data['data']['rewards'][0]['order_id']);
    }
}
