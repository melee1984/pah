<?php

namespace Tests\Feature;

use App\MerchantBranchUser;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MerchantBranchUserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

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
            $table->string('city')->nullable();
            $table->timestamps();
        });

        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Merchant Owner',
            'firstname' => 'Merchant',
            'lastname' => 'Owner',
            'email' => 'owner@example.com',
            'mobile' => '09170000000',
            'password' => Hash::make('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->owner = User::query()->findOrFail($ownerId);

        Role::findOrCreate('partner', 'web');
        $this->owner->assignRole('partner');

        DB::table('partners')->insert([
            'id' => 50,
            'user_id' => $this->owner->id,
            'restaurant_name' => 'Test Merchant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('partner_location')->insert([
            'id' => 500,
            'partner_id' => 50,
            'address_1' => 'Main Branch',
            'city' => 'Davao City',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_owner_can_create_a_user_assigned_to_a_branch(): void
    {
        $this->actingAs($this->owner)
            ->post('/merchant/users', [
                'firstname' => 'Branch',
                'lastname' => 'Operator',
                'email' => 'branch@example.com',
                'mobile' => '09171111111',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'partner_location_id' => 500,
            ])
            ->assertRedirect(route('merchant.dashboard.users.index'));

        $branchUser = User::query()->where('email', 'branch@example.com')->firstOrFail();
        $this->assertTrue($branchUser->hasRole('partner'));
        $this->assertSame(50, (int) $branchUser->merchant->id);
        $this->assertDatabaseHas('merchant_branch_users', [
            'user_id' => $branchUser->id,
            'partner_id' => 50,
            'partner_location_id' => 500,
            'active' => true,
        ]);
    }

    public function test_branch_is_required_when_creating_a_user(): void
    {
        $this->actingAs($this->owner)
            ->from('/merchant/users')
            ->post('/merchant/users', [
                'firstname' => 'Branch',
                'lastname' => 'Operator',
                'email' => 'branch@example.com',
                'mobile' => '09171111111',
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
            ])
            ->assertRedirect('/merchant/users')
            ->assertSessionHasErrors('partner_location_id');

        $this->assertDatabaseMissing('users', ['email' => 'branch@example.com']);
    }

    public function test_disabling_assignment_revokes_the_users_api_token(): void
    {
        $branchUserId = DB::table('users')->insertGetId([
            'name' => 'Branch Operator',
            'firstname' => 'Branch',
            'lastname' => 'Operator',
            'email' => 'branch@example.com',
            'mobile' => '09171111111',
            'password' => Hash::make('secret-password'),
            'api_token' => 'active-token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $assignment = MerchantBranchUser::query()->create([
            'user_id' => $branchUserId,
            'partner_id' => 50,
            'partner_location_id' => 500,
            'created_by_user_id' => $this->owner->id,
            'active' => true,
        ]);

        $this->actingAs($this->owner)
            ->patch('/merchant/users/'.$assignment->id, [
                'partner_location_id' => 500,
                'active' => 0,
            ])
            ->assertRedirect(route('merchant.dashboard.users.index'));

        $this->assertDatabaseHas('merchant_branch_users', ['id' => $assignment->id, 'active' => false]);
        $this->assertNull(User::query()->findOrFail($branchUserId)->api_token);
    }
}
