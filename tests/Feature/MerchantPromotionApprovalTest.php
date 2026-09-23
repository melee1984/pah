<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\PartnerPromotionController as AdminPartnerPromotionController;
use App\PartnerPromotion;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MerchantPromotionApprovalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partners', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->string('restaurant_name');
            $table->string('slug');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function test_merchant_submission_is_pending_and_scoped_to_the_logged_in_merchant(): void
    {
        $merchant = $this->merchant('merchant@example.com', 'My Store', 1);
        $otherMerchant = $this->merchant('other@example.com', 'Other Store', 2);

        $response = $this->actingAs($merchant)->post(route('merchant.dashboard.promotions.store'), [
            'name' => 'Weekend Special',
            'sort_order' => 1,
            'active' => 1,
            'image' => UploadedFile::fake()->image('promotion.jpg', 1200, 600),
        ]);

        $response->assertRedirect(route('merchant.dashboard.promotions.index'));
        $promotion = PartnerPromotion::query()->where('name', 'Weekend Special')->firstOrFail();
        $this->assertSame(1, (int) $promotion->partner_id);
        $this->assertSame(PartnerPromotion::APPROVAL_PENDING, $promotion->approval_status);
        $this->assertFalse(PartnerPromotion::query()->visible()->whereKey($promotion)->exists());

        $otherPromotion = PartnerPromotion::create([
            'partner_id' => 2,
            'name' => 'Other promotion',
            'image_path' => 'uploads/promotions/other.jpg',
            'approval_status' => PartnerPromotion::APPROVAL_PENDING,
        ]);

        $this->actingAs($merchant)
            ->get(route('merchant.dashboard.promotions.edit', $otherPromotion))
            ->assertNotFound();

        File::delete(public_path($promotion->image_path));
        unset($otherMerchant);
    }

    public function test_approved_promotion_becomes_visible_and_merchant_edit_requires_reapproval(): void
    {
        $merchant = $this->merchant('merchant@example.com', 'My Store', 1);
        $promotion = PartnerPromotion::create([
            'partner_id' => 1,
            'name' => 'Approved promotion',
            'image_path' => 'uploads/promotions/approved.jpg',
            'active' => true,
            'approval_status' => PartnerPromotion::APPROVAL_APPROVED,
            'approved_at' => now(),
        ]);

        $this->assertTrue(PartnerPromotion::query()->visible()->whereKey($promotion)->exists());

        $this->actingAs($merchant)->put(route('merchant.dashboard.promotions.update', $promotion), [
            'name' => 'Edited promotion',
            'sort_order' => 1,
            'active' => 1,
        ])->assertRedirect(route('merchant.dashboard.promotions.index'));

        $promotion->refresh();
        $this->assertSame(PartnerPromotion::APPROVAL_PENDING, $promotion->approval_status);
        $this->assertNull($promotion->approved_at);
        $this->assertFalse(PartnerPromotion::query()->visible()->whereKey($promotion)->exists());
    }

    public function test_administrator_approval_makes_a_pending_promotion_visible(): void
    {
        $merchant = $this->merchant('merchant@example.com', 'My Store', 1);
        $promotion = PartnerPromotion::create([
            'partner_id' => 1,
            'name' => 'Pending promotion',
            'image_path' => 'uploads/promotions/pending.jpg',
            'active' => true,
            'approval_status' => PartnerPromotion::APPROVAL_PENDING,
        ]);

        $this->actingAs($merchant);
        app(AdminPartnerPromotionController::class)->approve($promotion);

        $promotion->refresh();
        $this->assertSame(PartnerPromotion::APPROVAL_APPROVED, $promotion->approval_status);
        $this->assertNotNull($promotion->approved_at);
        $this->assertSame($merchant->id, $promotion->approved_by);
        $this->assertTrue(PartnerPromotion::query()->visible()->whereKey($promotion)->exists());
    }

    private function merchant(string $email, string $store, int $partnerId): User
    {
        $userId = DB::table('users')->insertGetId([
            'name' => $store,
            'email' => $email,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('partners')->insert([
            'id' => $partnerId,
            'user_id' => $userId,
            'restaurant_name' => $store,
            'slug' => str($store)->slug(),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($userId);
    }
}
