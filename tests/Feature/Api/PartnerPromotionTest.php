<?php

namespace Tests\Feature\Api;

use App\PartnerPromotion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerPromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_endpoint_returns_only_current_active_promotions_in_display_order(): void
    {
        Carbon::setTestNow('2026-09-20 12:00:00');

        Schema::create('partners', function (Blueprint $table) {
            $table->increments('id');
            $table->string('restaurant_name');
            $table->string('slug');
            $table->boolean('active')->default(true);
        });
        DB::table('partners')->insert([
            ['id' => 1, 'restaurant_name' => 'Pahatud Kitchen', 'slug' => 'pahatud-kitchen', 'active' => true],
            ['id' => 2, 'restaurant_name' => 'Closed Kitchen', 'slug' => 'closed-kitchen', 'active' => false],
        ]);

        $second = PartnerPromotion::create($this->promotionData([
            'name' => 'Second banner',
            'sort_order' => 20,
        ]));
        $first = PartnerPromotion::create($this->promotionData([
            'name' => 'First banner',
            'sort_order' => 10,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]));

        PartnerPromotion::create($this->promotionData([
            'name' => 'Inactive banner',
            'active' => false,
        ]));
        PartnerPromotion::create($this->promotionData([
            'name' => 'Future banner',
            'starts_at' => now()->addDay(),
        ]));
        PartnerPromotion::create($this->promotionData([
            'name' => 'Expired banner',
            'ends_at' => now()->subDay(),
        ]));
        PartnerPromotion::create($this->promotionData([
            'partner_id' => 2,
            'name' => 'Inactive merchant banner',
        ]));

        $response = $this->getJson('/api/promotions');

        $response
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('0.partner_id', 1)
            ->assertJsonPath('0.partner.name', 'Pahatud Kitchen')
            ->assertJsonPath('0.partner.slug', 'pahatud-kitchen')
            ->assertJsonPath('0.action.type', 'merchant')
            ->assertJsonPath('0.action.partner_id', 1)
            ->assertJsonPath('0.title', 'First banner')
            ->assertJsonPath('0.link_url', route('restaurant.view', 'pahatud-kitchen'))
            ->assertJsonPath('0.image', url('uploads/promotions/banner.jpg'))
            ->assertJsonPath('1.id', $second->id);
    }

    private function promotionData(array $overrides = []): array
    {
        return array_merge([
            'partner_id' => 1,
            'name' => 'Promotion',
            'image_path' => 'uploads/promotions/banner.jpg',
            'active' => true,
            'sort_order' => 0,
        ], $overrides);
    }
}
