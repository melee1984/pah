<?php

namespace Tests\Unit;

use App\Services\RestaurantService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class RestaurantServiceCityFilterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('city')->nullable();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('city')->nullable();
        });
    }

    public function test_it_falls_back_to_the_merchant_city_when_the_branch_city_is_blank(): void
    {
        $this->insertMerchant(1, 'Davao City', null);
        $this->insertMerchant(2, 'General Santos City', null);

        $this->assertSame([1], $this->partnerIdsForCity('Davao City'));
    }

    public function test_a_populated_branch_city_takes_precedence_over_the_merchant_city(): void
    {
        $this->insertMerchant(1, 'Davao City', 'General Santos City');
        $this->insertMerchant(2, 'General Santos City', 'Davao');

        $this->assertSame([2], $this->partnerIdsForCity('Davao City'));
    }

    private function insertMerchant(int $id, string $merchantCity, ?string $branchCity): void
    {
        DB::table('partners')->insert(['id' => $id, 'city' => $merchantCity]);
        DB::table('partner_location')->insert([
            'id' => $id,
            'partner_id' => $id,
            'city' => $branchCity,
        ]);
    }

    private function partnerIdsForCity(string $city): array
    {
        $query = DB::table('partners')
            ->join('partner_location', 'partner_location.partner_id', '=', 'partners.id')
            ->select('partners.id');

        $method = new ReflectionMethod(RestaurantService::class, 'filterByCity');
        $method->setAccessible(true);
        $method->invoke(null, $query, $city);

        return $query->orderBy('partners.id')->pluck('partners.id')->all();
    }
}
