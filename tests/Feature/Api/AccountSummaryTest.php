<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\AccountSummaryController;
use App\User;
use App\UserReward;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AccountSummaryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('lastname');
            $table->string('email');
            $table->string('mobile')->nullable();
            $table->string('avatar')->nullable();
        });
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->timestamp('submitted_at')->nullable();
        });
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
        });
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedInteger('points');
            $table->string('status', 20);
        });

        DB::table('users')->insert([
            [
                'id' => 5,
                'firstname' => 'Juan',
                'lastname' => 'Dela Cruz',
                'email' => 'juan@example.com',
                'mobile' => '09171234567',
                'avatar' => 'https://example.com/juan.jpg',
            ],
            [
                'id' => 6,
                'firstname' => 'Maria',
                'lastname' => 'Santos',
                'email' => 'maria@example.com',
                'mobile' => null,
                'avatar' => null,
            ],
        ]);
    }

    public function test_it_returns_the_authenticated_customers_account_totals(): void
    {
        DB::table('order')->insert([
            ['user_id' => 5, 'submitted_at' => now()],
            ['user_id' => 5, 'submitted_at' => now()],
            ['user_id' => 5, 'submitted_at' => null],
            ['user_id' => 6, 'submitted_at' => now()],
        ]);
        DB::table('user_favorites')->insert([
            ['user_id' => 5, 'partner_id' => 10],
            ['user_id' => 5, 'partner_id' => 11],
            ['user_id' => 6, 'partner_id' => 12],
        ]);
        DB::table('user_rewards')->insert([
            ['user_id' => 5, 'points' => 3, 'status' => UserReward::STATUS_EARNED],
            ['user_id' => 5, 'points' => 2, 'status' => UserReward::STATUS_EARNED],
            ['user_id' => 5, 'points' => 4, 'status' => UserReward::STATUS_REVERSED],
            ['user_id' => 6, 'points' => 99, 'status' => UserReward::STATUS_EARNED],
        ]);

        $request = Request::create('/api/mobile/account/summary', 'GET');
        $request->setUserResolver(fn ($guard = null) => User::query()->findOrFail(5));

        $data = (new AccountSummaryController)($request)->getData(true);

        $this->assertSame(1, $data['status']);
        $this->assertSame([
            'player' => [
                'id' => 5,
                'name' => 'Juan Dela Cruz',
                'firstname' => 'Juan',
                'lastname' => 'Dela Cruz',
                'email' => 'juan@example.com',
                'mobile' => '09171234567',
                'photo' => 'https://example.com/juan.jpg',
            ],
            'total_orders' => 2,
            'total_favorites' => 2,
            'points' => 5,
            'point_tier' => [
                'name' => 'Tier 1',
                'minimum_points' => 0,
                'maximum_points' => 100,
            ],
            'point_tiers' => [
                [
                    'name' => 'Tier 1',
                    'minimum_points' => 0,
                    'maximum_points' => 100,
                ],
                [
                    'name' => 'Tier 2',
                    'minimum_points' => 101,
                    'maximum_points' => 200,
                ],
                [
                    'name' => 'Tier 3',
                    'minimum_points' => 201,
                    'maximum_points' => 500,
                ],
            ],
        ], $data['data']);
    }

    public function test_it_selects_the_correct_point_tier_at_each_boundary(): void
    {
        $expectedTiers = [
            0 => 'Tier 1',
            100 => 'Tier 1',
            101 => 'Tier 2',
            200 => 'Tier 2',
            201 => 'Tier 3',
            500 => 'Tier 3',
            501 => 'Tier 3',
        ];

        $request = Request::create('/api/mobile/account/summary', 'GET');
        $request->setUserResolver(fn ($guard = null) => User::query()->findOrFail(5));

        foreach ($expectedTiers as $points => $expectedTier) {
            DB::table('user_rewards')->delete();

            if ($points > 0) {
                DB::table('user_rewards')->insert([
                    'user_id' => 5,
                    'points' => $points,
                    'status' => UserReward::STATUS_EARNED,
                ]);
            }

            $data = (new AccountSummaryController)($request)->getData(true);

            $this->assertSame($expectedTier, $data['data']['point_tier']['name']);
        }
    }
}
