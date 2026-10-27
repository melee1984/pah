<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\FavoriteController;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
        });
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
            $table->timestamps();
            $table->unique(['user_id', 'partner_id']);
        });

        DB::table('users')->insert(['id' => 5]);
        DB::table('partners')->insert([
            ['id' => 28, 'restaurant_name' => 'Pahatud Kitchen'],
            ['id' => 29, 'restaurant_name' => 'Cebu Grill'],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_customer_can_add_and_remove_a_favorite(): void
    {
        $controller = new FavoriteController;
        $user = User::query()->findOrFail(5);

        $response = $controller->store($this->request($user, [
            'partner_id' => 28,
            'is_favorite' => true,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseHas('user_favorites', [
            'user_id' => 5,
            'partner_id' => 28,
        ]);

        $controller->store($this->request($user, [
            'partner_id' => 28,
            'is_favorite' => false,
        ]));

        $this->assertDatabaseMissing('user_favorites', [
            'user_id' => 5,
            'partner_id' => 28,
        ]);
    }

    public function test_customer_receives_only_their_favorite_ids(): void
    {
        DB::table('user_favorites')->insert([
            [
                'user_id' => 5,
                'partner_id' => 28,
                'created_at' => now()->subMinute(),
                'updated_at' => now()->subMinute(),
            ],
            [
                'user_id' => 5,
                'partner_id' => 29,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $request = Request::create('/api/mobile/favorites', 'GET');
        $request->setUserResolver(fn ($guard = null) => User::query()->findOrFail(5));
        $data = (new FavoriteController)->index($request)->getData(true);

        $this->assertSame(1, $data['status']);
        $this->assertSame(['29', '28'], $data['data']['favorite_ids']);
    }

    private function request(User $user, array $data): Request
    {
        $request = Request::create('/api/mobile/favorites/submit', 'POST', $data);
        $request->setUserResolver(fn ($guard = null) => $user);

        return $request;
    }
}
