<?php

namespace Tests\Feature\Api;

use App\Http\Middleware\EnsureMerchantBranch;
use App\MerchantBranchUser;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MerchantLoginBranchTest extends TestCase
{
    use RefreshDatabase;

    private User $merchantUser;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::table('users', function (Blueprint $table) {
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('mobile')->nullable();
            $table->string('api_token', 100)->nullable();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('restaurant_name');
            $table->timestamps();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('address_1');
            $table->string('address_2')->nullable();
            $table->string('city')->nullable();
            $table->string('mobile')->nullable();
            $table->string('telephone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $userId = DB::table('users')->insertGetId([
            'name' => 'Merchant Owner',
            'firstname' => 'Merchant',
            'lastname' => 'Owner',
            'email' => 'merchant@example.com',
            'mobile' => '09171234567',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->merchantUser = User::query()->findOrFail($userId);

        Role::findOrCreate('partner', 'web');
        $this->merchantUser->assignRole('partner');

        $partnerId = DB::table('partners')->insertGetId([
            'user_id' => $this->merchantUser->id,
            'restaurant_name' => 'Test Restaurant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('partner_location')->insert([
            [
                'id' => 10,
                'partner_id' => $partnerId,
                'address_1' => 'Downtown Branch',
                'city' => 'Davao City',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'partner_id' => $partnerId,
                'address_1' => 'Uptown Branch',
                'city' => 'Davao City',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 99,
                'partner_id' => 999,
                'address_1' => 'Another Merchant Branch',
                'city' => 'Davao City',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        MerchantBranchUser::query()->create([
            'user_id' => $this->merchantUser->id,
            'partner_id' => $partnerId,
            'partner_location_id' => 11,
            'created_by_user_id' => $this->merchantUser->id,
            'active' => true,
        ]);
    }

    public function test_login_returns_only_the_users_designated_branch(): void
    {
        $this->postMerchantLogin()
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonCount(1, 'branches')
            ->assertJsonPath('merchant_location_id', 11)
            ->assertJsonPath('selected_branch.id', 11)
            ->assertJsonPath('requires_branch_selection', false);
    }

    public function test_client_cannot_override_the_designated_branch(): void
    {
        $this->postMerchantLogin(['merchant_location_id' => 10])
            ->assertOk()
            ->assertJsonPath('merchant_location_id', 11)
            ->assertJsonPath('selected_branch.id', 11)
            ->assertJsonPath('requires_branch_selection', false);
    }

    public function test_login_is_denied_without_an_active_branch_assignment(): void
    {
        MerchantBranchUser::query()->where('user_id', $this->merchantUser->id)->update(['active' => false]);

        $this->postMerchantLogin()
            ->assertForbidden()
            ->assertJsonPath('status', 0)
            ->assertJsonPath('message', 'No active merchant branch is assigned to this login. Please contact the merchant account owner.');
    }

    public function test_branch_middleware_ignores_client_branch_overrides(): void
    {
        $request = Request::create('/api/merchant/orders', 'GET', [
            'merchant_location_id' => 10,
            'store_location_id' => 10,
        ]);
        $request->setUserResolver(fn () => $this->merchantUser);

        $response = (new EnsureMerchantBranch)->handle($request, fn (Request $securedRequest) => response()->json([
            'merchant_location_id' => $securedRequest->integer('merchant_location_id'),
            'store_location_id' => $securedRequest->integer('store_location_id'),
            'assigned_branch_id' => $securedRequest->attributes->get('merchantBranch')->id,
        ]));

        $this->assertSame([
            'merchant_location_id' => 11,
            'store_location_id' => 11,
            'assigned_branch_id' => 11,
        ], $response->getData(true));
    }

    private function postMerchantLogin(array $overrides = [])
    {
        return $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/merchant/account/login', array_merge([
                'email' => $this->merchantUser->email,
                'password' => 'password',
            ], $overrides));
    }
}
