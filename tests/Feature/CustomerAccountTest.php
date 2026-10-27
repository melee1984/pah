<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CustomerAccountTest extends TestCase
{
    private User $customer;

    private User $other;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('c', 32))]);
        DB::purge('sqlite');
        $this->withoutVite();
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('firstname');
            $t->string('lastname');
            $t->string('email')->unique();
            $t->string('mobile');
            $t->string('password');
            $t->timestamp('email_verified_at')->nullable();
            $t->rememberToken();
            $t->timestamps();
        });
        Schema::create('order', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('cart_id');
            $t->unsignedBigInteger('order_status_id')->nullable();
            $t->unsignedBigInteger('booking_status_id')->nullable();
            $t->timestamps();
        });
        Schema::create('cart', function (Blueprint $t) {
            $t->id();
            $t->string('order_no')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('session_id')->nullable();
            $t->unsignedBigInteger('partner_id')->nullable();
            $t->unsignedBigInteger('payment_id')->nullable();
            $t->timestamps();
        });
        Schema::create('payment_method', function (Blueprint $t) {
            $t->id();
            $t->string('title');
        });
        Schema::create('partners', function (Blueprint $t) {
            $t->id();
            $t->string('restaurant_name');
        });
        Schema::create('library_status', function (Blueprint $t) {
            $t->id();
            $t->string('title');
        });
        Schema::create('library_booking_status', function (Blueprint $t) {
            $t->id();
            $t->string('title');
        });
        Schema::create('cart_details', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cart_id');
            $t->unsignedBigInteger('item_id')->nullable();
            $t->integer('qty');
            $t->decimal('price');
            $t->decimal('variance_total')->default(0);
        });
        Schema::create('cart_user_address', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('cart_id');
        });
        (require database_path('migrations/2026_10_02_000000_create_support_tables.php'))->up();
        $this->customer = User::create(['firstname' => 'Ana', 'lastname' => 'Cruz', 'email' => 'ana@example.test', 'mobile' => '09171234567', 'password' => Hash::make('secret-password')]);
        $this->other = User::create(['firstname' => 'Ben', 'lastname' => 'Santos', 'email' => 'ben@example.test', 'mobile' => '09171234568', 'password' => Hash::make('secret-password')]);
    }

    public function test_all_customer_pages_require_the_same_login(): void
    {
        foreach (['/dashboard', '/profile/orders', '/profile', '/profile/support'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_website_login_rotates_session_keeps_cart_and_opens_dashboard(): void
    {
        $this->withSession(['cart-test' => true]);
        $oldSession = session()->getId();
        DB::table('cart')->insert(['session_id' => $oldSession]);
        $this->withCookie(config('session.cookie'), $oldSession);
        $this->post('/login/submit', ['email' => $this->customer->email, 'password' => 'secret-password'])->assertRedirect(route('profile.dashboard'));
        $this->assertAuthenticatedAs($this->customer);
        $this->assertNotSame($oldSession, session()->getId());
        $this->assertDatabaseHas('cart', ['user_id' => $this->customer->id, 'session_id' => session()->getId()]);
        $this->get('/dashboard')->assertOk()->assertSee('Ana Cruz')->assertSee('My Support Requests')->assertSee('Log Out');
        $this->get('/profile')->assertOk()->assertSee('ana@example.test');
        $this->get('/profile/orders')->assertOk()->assertSee('No orders yet');
        $this->get('/profile/support')->assertOk()->assertSee('full-page authenticated', false);
        $this->getJson('/support/options')->assertOk()->assertJsonPath('customer.name', 'Ana Cruz');
    }

    public function test_support_login_returns_to_support_and_shares_dashboard_session(): void
    {
        $this->get('/profile/support')->assertRedirect(route('login'));
        $this->post('/login/submit', ['email' => $this->customer->email, 'password' => 'secret-password'])->assertRedirect('/profile/support');
        $this->get('/dashboard')->assertOk();
        $this->getJson('/support/tickets')->assertOk();
        $this->get('/login')->assertRedirect(route('profile.dashboard'));
    }

    public function test_modal_login_uses_same_web_account(): void
    {
        $this->postJson('/api/login/submit', ['email' => $this->customer->email, 'password' => 'secret-password'])->assertOk()->assertJsonPath('status', 1)->assertJsonPath('redirectURL', route('profile.dashboard'));
        $this->assertAuthenticatedAs($this->customer);
        $this->get('/profile')->assertOk();
        $this->getJson('/support/options')->assertJsonPath('customer.email', $this->customer->email);
    }

    public function test_bad_password_does_not_authenticate(): void
    {
        $this->postJson('/login/submit', ['email' => $this->customer->email, 'password' => 'wrong-password'])->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_profile_updates_only_current_customer_and_allowed_fields(): void
    {
        $this->actingAs($this->customer)->patch('/profile', ['firstname' => 'Anna', 'lastname' => 'Reyes', 'email' => 'anna@example.test', 'mobile' => '+639171234567', 'id' => $this->other->id, 'account_type_id' => 1, 'password' => 'changed'])->assertRedirect(route('profile.edit'))->assertSessionHas('success');
        $this->assertDatabaseHas('users', ['id' => $this->customer->id, 'firstname' => 'Anna', 'email' => 'anna@example.test']);
        $this->assertDatabaseHas('users', ['id' => $this->other->id, 'firstname' => 'Ben']);
        $this->assertTrue(Hash::check('secret-password', $this->customer->fresh()->password));
        $this->get('/profile/support')->assertSee('Anna Reyes');
        $this->getJson('/support/options')->assertJsonPath('customer.name', 'Anna Reyes');
        $this->patchJson('/profile', ['firstname' => 'Anna', 'lastname' => 'Reyes', 'email' => $this->other->email, 'mobile' => '09171234567'])->assertUnprocessable()->assertJsonValidationErrors('email');
    }

    public function test_order_history_and_details_are_scoped_to_owner(): void
    {
        DB::table('library_status')->insert(['id' => 3, 'title' => 'Preparing your order']);
        DB::table('cart')->insert([
            ['id' => 10, 'order_no' => 'MY-1001', 'user_id' => $this->customer->id],
            ['id' => 11, 'order_no' => 'PRIVATE-2002', 'user_id' => $this->other->id],
        ]);
        DB::table('order')->insert([
            ['id' => 10, 'user_id' => $this->customer->id, 'cart_id' => 10, 'order_status_id' => 3, 'created_at' => now()],
            ['id' => 11, 'user_id' => $this->other->id, 'cart_id' => 11, 'order_status_id' => 3, 'created_at' => now()],
        ]);
        $this->actingAs($this->customer)->get('/profile/orders')->assertOk()->assertSee('MY-1001')->assertSee('Preparing your order')->assertDontSee('PRIVATE-2002');
        $this->get('/profile/order/PRIVATE-2002')->assertNotFound();
        $this->get('/profile/order/MY-1001')->assertOk()->assertSee('Preparing your order')->assertSee('Order information');
    }

    public function test_logout_revokes_access_to_dashboard_and_support(): void
    {
        $this->actingAs($this->customer)->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->get('/dashboard')->assertRedirect(route('login'));
        $this->getJson('/support/tickets')->assertUnauthorized();
    }
}
