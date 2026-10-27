<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\Admin\FavoriteReportController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FavoriteReportTest extends TestCase
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
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name');
            $table->boolean('active')->default(true);
        });
        Schema::create('user_favorites', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
            $table->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 5, 'firstname' => 'Maria', 'lastname' => 'Santos', 'email' => 'maria@example.test', 'mobile' => '09170000005'],
            ['id' => 6, 'firstname' => 'Jose', 'lastname' => 'Reyes', 'email' => 'jose@example.test', 'mobile' => '09170000006'],
        ]);
        DB::table('partners')->insert([
            ['id' => 28, 'restaurant_name' => 'Pahatud Kitchen', 'active' => true],
            ['id' => 29, 'restaurant_name' => 'Cebu Grill', 'active' => true],
        ]);
        DB::table('user_favorites')->insert([
            ['user_id' => 5, 'partner_id' => 28, 'created_at' => now()->subDay(), 'updated_at' => now()->subDay()],
            ['user_id' => 6, 'partner_id' => 28, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 5, 'partner_id' => 29, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('user_favorites');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('users');

        parent::tearDown();
    }

    public function test_report_filters_and_ranks_hearted_merchants(): void
    {
        $request = Request::create('/data/dashboard/report/favorites', 'GET', [
            'merchant_id' => 28,
        ]);

        $view = (new FavoriteReportController)($request);
        $data = $view->getData();

        $this->assertSame('dashboard.pages.report.favorites', $view->name());
        $this->assertSame(2, $data['metrics']['favorites']);
        $this->assertSame(2, $data['metrics']['customers']);
        $this->assertSame(1, $data['metrics']['merchants']);
        $this->assertSame(2, $data['favorites']->total());
        $this->assertSame('Pahatud Kitchen', $data['topMerchants']->first()->partner->restaurant_name);
        $this->assertSame(2, $data['topMerchants']->first()->favorites_count);
    }
}
