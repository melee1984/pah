<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\RestaurantReviewController;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RestaurantReviewTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
        });
        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
        });
        Schema::create('restaurant_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('partner_location_id')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        DB::table('users')->insert([
            'id' => 5,
            'name' => 'Maria Santos',
            'firstname' => 'Maria',
            'lastname' => 'Santos',
        ]);
        DB::table('partners')->insert([
            'id' => 28,
            'restaurant_name' => 'Pahatud Kitchen',
        ]);
        DB::table('partner_location')->insert([
            'id' => 7,
            'partner_id' => 28,
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('restaurant_reviews');
        Schema::dropIfExists('partner_location');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_customer_can_submit_and_update_a_restaurant_review(): void
    {
        $controller = new RestaurantReviewController;
        $user = User::query()->findOrFail(5);

        $request = Request::create('/api/mobile/restaurant/review/submit', 'POST', [
            'restaurant_id' => 28,
            'partner_location_id' => 7,
            'rating' => 5,
            'comment' => 'Fast and delicious.',
        ]);
        $request->setUserResolver(fn ($guard = null) => $user);

        $response = $controller->store($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('Maria Santos', $response->getData(true)['data']['review']['reviewer_name']);
        $this->assertDatabaseHas('restaurant_reviews', [
            'user_id' => 5,
            'partner_id' => 28,
            'partner_location_id' => 7,
            'rating' => 5,
            'comment' => 'Fast and delicious.',
        ]);

        $request = Request::create('/api/mobile/restaurant/review/submit', 'POST', [
            'restaurant_id' => 28,
            'partner_location_id' => 7,
            'rating' => 4,
            'comment' => 'Still very good.',
        ]);
        $request->setUserResolver(fn ($guard = null) => $user);

        $response = $controller->store($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, DB::table('restaurant_reviews')->count());
        $this->assertDatabaseHas('restaurant_reviews', [
            'rating' => 4,
            'comment' => 'Still very good.',
        ]);
    }

    public function test_reviews_can_be_listed_for_a_restaurant_location(): void
    {
        DB::table('restaurant_reviews')->insert([
            'user_id' => 5,
            'partner_id' => 28,
            'partner_location_id' => 7,
            'rating' => 5,
            'comment' => 'Fast and delicious.',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request = Request::create('/api/mobile/restaurant/reviews', 'GET', [
            'restaurant_id' => 28,
            'partner_location_id' => 7,
        ]);
        $response = (new RestaurantReviewController)->index($request);
        $data = $response->getData(true);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $data['status']);
        $this->assertEquals(5.0, $data['data']['summary']['average_rating']);
        $this->assertSame(1, $data['data']['summary']['rating_count']);
        $this->assertSame('Fast and delicious.', $data['data']['reviews'][0]['comment']);
    }
}
