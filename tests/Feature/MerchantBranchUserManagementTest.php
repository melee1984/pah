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
                'role' => 'kitchen',
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
        $this->assertTrue($branchUser->hasRole('kitchen'));
        $this->assertFalse($branchUser->hasRole('partner'));
        $this->assertTrue($branchUser->hasPermissionTo('branch.orders.prepare'));
        $this->assertFalse($branchUser->hasPermissionTo('branch.payments.record'));
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

    private function staff(string $role = 'kitchen'): User
    {
        $user = User::forceCreate([
            'name' => 'Staff Member', 'firstname' => 'Staff', 'lastname' => 'Member',
            'email' => $role.'@example.com', 'password' => Hash::make('password'),
            'api_token' => $role.'-token',
        ]);
        $user->assignRole($role);
        MerchantBranchUser::create([
            'user_id' => $user->id, 'partner_id' => 50, 'partner_location_id' => 500,
            'created_by_user_id' => $this->owner->id, 'active' => true,
        ]);

        return $user;
    }

    public function test_owner_sees_role_selection_and_current_staff_role(): void
    {
        $this->withoutVite();
        $this->staff();
        $this->actingAs($this->owner)->get('/merchant/users')->assertOk()
            ->assertSee('name="role"', false)->assertSee('Kitchen')->assertSee('Cashier');
    }

    public function test_role_is_required_and_owner_roles_cannot_be_submitted(): void
    {
        $payload = [
            'firstname' => 'Staff', 'lastname' => 'Member', 'email' => 'staff@example.com',
            'mobile' => '09171111111', 'password' => 'secret-password',
            'password_confirmation' => 'secret-password', 'partner_location_id' => 500,
        ];
        $this->actingAs($this->owner)->post('/merchant/users', $payload)->assertSessionHasErrors('role');
        $this->post('/merchant/users', $payload + ['role' => 'partner'])->assertSessionHasErrors('role');
        $this->post('/merchant/users', $payload + ['role' => 'admin'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'staff@example.com']);
    }

    public function test_staff_are_redirected_to_their_workspace_and_denied_owner_pages(): void
    {
        foreach (['kitchen', 'cashier'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get('/merchant/dashboard')->assertRedirect(route('merchant.staff.index'));
            foreach (['users', 'settings', 'location', 'products', 'orders', 'tables'] as $page) {
                $this->get('/merchant/'.$page)->assertForbidden();
            }
            $this->post('/merchant/users', [])->assertForbidden();
        }
    }

    public function test_staff_cannot_use_legacy_merchant_apis_with_session_or_token(): void
    {
        $user = $this->staff();
        $this->actingAs($user)->getJson('/api/merchant/order/list')->assertForbidden();
        $this->postJson('/api/merchant/location/500/tables', ['name' => 'Changed'])->assertForbidden();
        auth()->forgetGuards();
        $this->withToken('kitchen-token')->getJson('/api/merchant/order/list')->assertForbidden();
    }

    public function test_disabled_and_inconsistent_assignments_are_denied(): void
    {
        $user = $this->staff();
        $assignment = MerchantBranchUser::where('user_id', $user->id)->firstOrFail();
        $assignment->update(['active' => false]);
        $this->actingAs($user)->get('/merchant/staff')->assertForbidden();
        $assignment->update(['active' => true, 'partner_id' => 999]);
        $this->get('/merchant/staff')->assertForbidden();
    }

    public function test_owner_can_change_role_and_existing_token_is_revoked(): void
    {
        $user = $this->staff();
        $assignment = MerchantBranchUser::where('user_id', $user->id)->firstOrFail();
        $this->actingAs($this->owner)->patch('/merchant/users/'.$assignment->id, [
            'role' => 'cashier', 'partner_location_id' => 500, 'active' => 1,
        ])->assertRedirect(route('merchant.dashboard.users.index'));
        $user = $user->fresh();
        $this->assertTrue($user->hasRole('cashier'));
        $this->assertFalse($user->hasRole('kitchen'));
        $this->assertTrue($user->hasPermissionTo('branch.payments.record'));
        $this->assertFalse($user->hasPermissionTo('branch.orders.prepare'));
        $this->assertNull($user->api_token);
    }

    public function test_staff_login_ignores_an_owner_page_intended_redirect(): void
    {
        $this->staff();
        $this->withSession(['url.intended' => '/merchant/settings'])
            ->post('/merchant/dashboard/login/submit', ['email' => 'kitchen@example.com', 'password' => 'password'])
            ->assertRedirect(route('merchant.staff.index'));
    }

    public function test_staff_workspace_only_shows_assigned_branch_orders_and_cashier_tables(): void
    {
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('partner_id');
            $table->integer('order_status_id');
            $table->dateTime('submitted_at');
            $table->timestamps();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_location_address_id');
            $table->unsignedBigInteger('dining_table_id')->nullable();
            $table->string('order_no');
            $table->string('fulfillment_type')->default('dine_in');
        });
        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('qty');
            $table->string('instruction')->nullable();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
        Schema::create('library_status', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });
        DB::table('library_status')->insert(['id' => 1, 'title' => 'Order Placed']);
        DB::table('cart')->insert([
            ['id' => 1, 'partner_location_address_id' => 500, 'order_no' => 'VISIBLE500'],
            ['id' => 2, 'partner_location_address_id' => 501, 'order_no' => 'PRIVATE501'],
        ]);
        DB::table('order')->insert([
            ['cart_id' => 1, 'partner_id' => 50, 'order_status_id' => 1, 'submitted_at' => now()],
            ['cart_id' => 2, 'partner_id' => 50, 'order_status_id' => 1, 'submitted_at' => now()],
        ]);
        DB::table('partner_location_tables')->insert([
            'partner_location_id' => 500, 'name' => 'Cashier table', 'capacity' => 4,
        ]);
        Schema::create('payment_method', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->boolean('available_in_qr')->default(true);
        });
        foreach (['kitchen', 'cashier'] as $role) {
            $response = $this->actingAs($this->staff($role))->get('/merchant/staff?merchant_location_id=501');
            $response->assertOk()->assertSee('VISIBLE500')->assertDontSee('PRIVATE501');
            if ($role === 'cashier') {
                $response->assertSee('Cashier table');
            } else {
                $response->assertDontSee('Cashier table')->assertSee('merchant-staff-workspace');
            }
        }
    }

    public function test_regular_customer_cannot_access_merchant_pages(): void
    {
        $user = $this->staff();
        $user->syncRoles([]);
        $this->actingAs($user)->get('/merchant/settings')->assertForbidden();
    }
}
