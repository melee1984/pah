<?php

namespace Tests\Feature;

use App\Agent;
use App\AgentCommission;
use App\Http\Middleware\isAdmin;
use App\LibraryStatus;
use App\Mail\AgentTemporaryPasswordMail;
use App\Mail\RestaurantInvitationMail;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Orders\Orders;
use App\Partners;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AgentPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->string('restaurant_name');
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('telephone')->nullable();
            $table->text('address')->nullable();
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('slug')->nullable();
            $table->text('search_string')->nullable();
            $table->boolean('active')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->decimal('delivery_fee', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('cart_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedInteger('qty');
            $table->decimal('price', 12, 2);
            $table->decimal('variance_total', 12, 2)->default(0);
            $table->decimal('price_comm_total', 12, 2)->default(0);
            $table->decimal('variance_total_comm_total', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->string('order_no');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('partner_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedInteger('order_status_id')->nullable();
            $table->unsignedInteger('booking_status_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_agent_can_log_in_and_inactive_agent_cannot(): void
    {
        $active = Agent::query()->create([
            'name' => 'Active Agent',
            'email' => 'active@example.com',
            'password' => 'password123',
            'commission_percentage' => 30,
            'active' => true,
        ]);

        $this->post(route('agent.login.store'), [
            'email' => $active->email,
            'password' => 'password123',
        ])->assertRedirect(route('agent.dashboard'));

        $this->post(route('agent.logout'))->assertRedirect(route('agent.login'));

        Agent::query()->create([
            'name' => 'Inactive Agent',
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'commission_percentage' => 30,
            'active' => false,
        ]);

        $this->post(route('agent.login.store'), [
            'email' => 'inactive@example.com',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');
    }

    public function test_temporary_password_must_be_replaced_before_portal_access(): void
    {
        $agent = Agent::query()->create([
            'name' => 'Invited Agent',
            'email' => 'invited-agent@example.com',
            'password' => 'Temporary123',
            'commission_percentage' => 10,
            'active' => true,
            'must_change_password' => true,
            'temporary_password_created_at' => now(),
        ]);

        $this->post(route('agent.login.store'), [
            'email' => $agent->email,
            'password' => 'Temporary123',
        ])->assertRedirect(route('agent.password.edit'));

        $this->get(route('agent.dashboard'))
            ->assertRedirect(route('agent.password.edit'));

        $this->post(route('agent.password.update'), [
            'current_password' => 'Temporary123',
            'password' => 'PrivatePassword456',
            'password_confirmation' => 'PrivatePassword456',
        ])->assertRedirect(route('agent.dashboard'));

        $agent->refresh();
        $this->assertFalse($agent->must_change_password);
        $this->assertNotNull($agent->password_changed_at);
        $this->assertTrue(Hash::check('PrivatePassword456', $agent->password));
        $this->assertFalse(Hash::check('Temporary123', $agent->password));
    }

    public function test_admin_can_create_an_agent_and_email_a_temporary_password(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);

        $this->post(route('dashboard.agents.store'), [
            'name' => 'New Agent',
            'email' => 'new-agent@example.com',
            'mobile' => '09171234567',
            'commission_percentage' => 10,
        ])->assertRedirect(route('dashboard.agents.index'));

        $agent = Agent::query()->where('email', 'new-agent@example.com')->firstOrFail();
        $this->assertTrue($agent->active);
        $this->assertTrue($agent->must_change_password);
        $this->assertNotNull($agent->temporary_password_created_at);
        $this->assertSame('10.00', $agent->commission_percentage);

        Mail::assertSent(AgentTemporaryPasswordMail::class, function ($mail) use ($agent) {
            $this->assertTrue(Hash::check($mail->temporaryPassword, $agent->password));

            return $mail->hasTo('new-agent@example.com');
        });
    }

    public function test_admin_agent_page_lists_registered_agents(): void
    {
        $this->agent('first-listing@example.com')->update(['name' => 'First Listed Agent']);
        $this->agent('second-listing@example.com')->update(['name' => 'Second Listed Agent']);
        $admin = User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => 'admin-listing@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->withoutMiddleware(isAdmin::class);

        $this->actingAs($admin)
            ->get(route('dashboard.agents.index'))
            ->assertOk()
            ->assertSee('First Listed Agent')
            ->assertSee('Second Listed Agent')
            ->assertSee('Add new agent');
    }

    public function test_restaurant_pages_are_scoped_to_the_logged_in_agent(): void
    {
        $agent = $this->agent('one@example.com');
        $otherAgent = $this->agent('two@example.com');
        $own = $this->restaurant($agent, 'My Inasal', 'inasal@example.com');
        $other = $this->restaurant($otherAgent, 'Other Cafe', 'other@example.com');

        $response = $this->actingAs($agent, 'agent')->get(route('agent.restaurants.index'));

        $response->assertOk()->assertSee($own->restaurant_name)->assertDontSee($other->restaurant_name);
    }

    public function test_enrollment_automatically_links_the_restaurant_to_the_agent(): void
    {
        Mail::fake();
        $agent = $this->agent();

        $this->actingAs($agent, 'agent')->post(route('agent.restaurants.store'), [
            'restaurant_name' => 'Inasal House',
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'email' => 'inasal@example.com',
            'mobile' => '09171234567',
            'address' => '123 Test Street',
            'city' => 'Davao City',
            'description' => 'Local grilled favorites.',
        ])->assertRedirect(route('agent.restaurants.index'));

        $this->assertDatabaseHas('partners', [
            'restaurant_name' => 'Inasal House',
            'agent_id' => $agent->id,
            'active' => false,
        ]);
        $this->assertDatabaseHas('users', ['email' => 'inasal@example.com', 'name' => 'Maria Santos']);
        $user = User::query()->where('email', 'inasal@example.com')->firstOrFail();
        $restaurant = Partners::query()->where('email', 'inasal@example.com')->firstOrFail();
        $this->assertSame($user->id, $restaurant->user_id);
        $this->assertDatabaseHas('restaurant_invitations', [
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'email' => 'inasal@example.com',
            'accepted_at' => null,
        ]);
        Mail::assertSent(RestaurantInvitationMail::class, fn ($mail) => $mail->hasTo('inasal@example.com'));
    }

    public function test_restaurant_contact_can_complete_the_one_time_invitation(): void
    {
        Mail::fake();
        $agent = $this->agent();

        $this->actingAs($agent, 'agent')->post(route('agent.restaurants.store'), [
            'restaurant_name' => 'Invitation Cafe',
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'email' => 'invited@example.com',
            'mobile' => '09170000000',
            'address' => '123 Invitation Street',
            'city' => 'Davao City',
        ])->assertRedirect(route('agent.restaurants.index'));

        $token = null;
        Mail::assertSent(RestaurantInvitationMail::class, function ($mail) use (&$token) {
            $token = basename(parse_url($mail->invitationUrl, PHP_URL_PATH));
            $this->assertStringContainsString('Invitation Cafe', $mail->render());

            return $mail->hasTo('invited@example.com');
        });

        $this->assertNotNull($token);
        $this->get(route('restaurant.invitation.show', $token))
            ->assertOk()
            ->assertSee('Invitation Cafe')
            ->assertSee('invited@example.com');

        $this->post(route('restaurant.invitation.update', $token), [
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'mobile' => '09171111111',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('merchant.status'));

        $user = User::query()->where('email', 'invited@example.com')->firstOrFail();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertDatabaseMissing('restaurant_invitations', [
            'email' => 'invited@example.com',
            'accepted_at' => null,
        ]);
        $this->post(route('restaurant.invitation.update', $token), [
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'mobile' => '09171111111',
            'password' => 'another-password',
            'password_confirmation' => 'another-password',
        ])->assertGone();
    }

    public function test_commission_report_only_contains_the_logged_in_agents_transactions(): void
    {
        $agent = $this->agent('report@example.com');
        $otherAgent = $this->agent('other-report@example.com');
        $ownRestaurant = $this->restaurant($agent, 'Own Restaurant', 'own@example.com');
        $otherRestaurant = $this->restaurant($otherAgent, 'Hidden Restaurant', 'hidden@example.com');

        AgentCommission::query()->create([
            'order_id' => 100,
            'restaurant_id' => $ownRestaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 100,
            'commission_percentage' => 30,
            'commission_amount' => 30,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);
        AgentCommission::query()->create([
            'order_id' => 101,
            'restaurant_id' => $otherRestaurant->id,
            'agent_id' => $otherAgent->id,
            'order_amount' => 200,
            'commission_percentage' => 30,
            'commission_amount' => 60,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.reports.index'))
            ->assertOk()
            ->assertSee('Own Restaurant')
            ->assertDontSee('Hidden Restaurant')
            ->assertSee('₱30.00')
            ->assertDontSee('₱60.00');
    }

    public function test_delivered_order_snapshots_commission_and_cancellation_reverses_it(): void
    {
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $cart = Cart::query()->create(['partner_id' => $restaurant->id, 'delivery_fee' => 0, 'discount_amount' => 0]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'qty' => 1,
            'price' => 100,
            'variance_total' => 0,
            'price_comm_total' => 0,
            'variance_total_comm_total' => 0,
            'discount_amount' => 0,
        ]);
        $order = Orders::query()->create([
            'order_no' => 'A-100',
            'cart_id' => $cart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);

        $order->update(['order_status_id' => LibraryStatus::STATUS_DELIVERED, 'delivered_at' => now()]);

        $this->assertDatabaseHas('agent_commissions', [
            'order_id' => $order->id,
            'agent_id' => $agent->id,
            'order_amount' => 100,
            'commission_percentage' => 30,
            'commission_amount' => 4.5,
            'status' => AgentCommission::STATUS_PENDING,
        ]);

        $agent->update(['commission_percentage' => 40]);
        $order->touch();
        $this->assertSame('30.00', $order->agentCommission()->first()->commission_percentage);

        $order->update(['order_status_id' => LibraryStatus::STATUS_CANCELLED]);
        $this->assertDatabaseHas('agent_commissions', [
            'order_id' => $order->id,
            'status' => AgentCommission::STATUS_REVERSED,
        ]);
    }

    private function agent(string $email = 'agent@example.com'): Agent
    {
        return Agent::query()->create([
            'name' => 'Test Agent',
            'email' => $email,
            'password' => 'password123',
            'commission_percentage' => 30,
            'active' => true,
        ]);
    }

    private function restaurant(Agent $agent, string $name = 'Test Restaurant', string $email = 'restaurant@example.com'): Partners
    {
        return $agent->restaurants()->create([
            'restaurant_name' => $name,
            'email' => $email,
            'mobile' => '09171234567',
            'address' => 'Davao City',
            'city' => 'Davao City',
            'slug' => str($name)->slug(),
            'search_string' => $name,
            'active' => true,
            'verified_at' => now(),
        ]);
    }
}
