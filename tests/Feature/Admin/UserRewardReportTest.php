<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\UserRewardReportController;
use App\User;
use App\UserReward;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class UserRewardReportTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
        });
        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedInteger('points');
            $table->decimal('order_amount', 12, 2);
            $table->decimal('php_per_point', 12, 2);
            $table->string('status', 20);
            $table->timestamp('earned_at');
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 5, 'firstname' => 'Maria', 'lastname' => 'Santos', 'email' => 'maria@example.test', 'mobile' => '09170000005'],
            ['id' => 6, 'firstname' => 'Jose', 'lastname' => 'Reyes', 'email' => 'jose@example.test', 'mobile' => '09170000006'],
        ]);
        DB::table('user_rewards')->insert([
            $this->reward(5, 101, 4, 450, UserReward::STATUS_EARNED, now()->subDay()),
            $this->reward(5, 102, 2, 250, UserReward::STATUS_REVERSED, now()),
            $this->reward(6, 103, 8, 800, UserReward::STATUS_EARNED, now()),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_report_summarizes_each_customers_points(): void
    {
        $view = (new UserRewardReportController)(Request::create(
            '/data/dashboard/report/user-rewards',
            'GET',
        ));
        $data = $view->getData();

        $this->assertSame('dashboard.pages.report.user-rewards', $view->name());
        $this->assertSame(12, $data['metrics']['available_points']);
        $this->assertSame(2, $data['metrics']['customers']);
        $this->assertSame(2, $data['metrics']['rewarded_orders']);
        $this->assertSame(2, $data['metrics']['reversed_points']);
        $this->assertSame(1250.0, $data['metrics']['qualifying_spend']);
        $this->assertSame(6, $data['customers']->first()->user_id);
        $this->assertEquals(8, $data['customers']->first()->available_points);

        Auth::setUser(User::query()->findOrFail(5));
        $view->with('errors', new ViewErrorBag);
        $this->assertStringContainsString('User Reward Points Report', $view->render());
    }

    public function test_report_can_find_one_customer(): void
    {
        $request = Request::create('/data/dashboard/report/user-rewards', 'GET', [
            'search' => 'Maria',
        ]);
        $data = (new UserRewardReportController)($request)->getData();

        $this->assertSame(1, $data['customers']->total());
        $this->assertSame(4, $data['metrics']['available_points']);
        $this->assertSame(2, $data['metrics']['reversed_points']);
        $this->assertSame('Maria', $data['customers']->first()->firstname);
    }

    private function reward(
        int $userId,
        int $orderId,
        int $points,
        float $orderAmount,
        string $status,
        mixed $earnedAt,
    ): array {
        return [
            'user_id' => $userId,
            'order_id' => $orderId,
            'points' => $points,
            'order_amount' => $orderAmount,
            'php_per_point' => 100,
            'status' => $status,
            'earned_at' => $earnedAt,
            'reversed_at' => $status === UserReward::STATUS_REVERSED ? now() : null,
            'reversal_reason' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
