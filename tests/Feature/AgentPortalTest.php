<?php

namespace Tests\Feature;

use App\Agent;
use App\AgentCommission;
use App\AgentStatementAccount;
use App\City;
use App\Http\Middleware\isAdmin;
use App\LibraryStatus;
use App\Mail\AgentApprovedMail;
use App\Mail\AgentDeclinedMail;
use App\Mail\AgentRegistrationReceivedMail;
use App\Mail\AgentTemporaryPasswordMail;
use App\Mail\RestaurantApplicationStatusMail;
use App\Mail\RestaurantInvitationMail;
use App\Model\Bookings\BookingStatus;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Orders\Orders;
use App\PartnerPromotion;
use App\Partners;
use App\RestaurantEnrollmentDocument;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
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
            $table->string('img')->nullable();
            $table->string('banner')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('active')->default(false);
            $table->unsignedInteger('account_type_id')->nullable();
            $table->boolean('addup')->default(false);
            $table->string('business_structure', 30)->nullable();
            $table->string('enrolling_as', 30)->nullable();
            $table->string('registered_business_name')->nullable();
            $table->string('tin', 30)->nullable();
            $table->string('business_registration_number', 100)->nullable();
            $table->string('payout_account_name')->nullable();
            $table->string('application_status', 30)->nullable();
            $table->text('application_remarks')->nullable();
            $table->string('application_source', 40)->nullable()->index();
            $table->string('recruitment_status', 20)->nullable()->index();
            $table->text('recruitment_review_message')->nullable();
            $table->timestamp('recruitment_submitted_at')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('cuisine', 120)->nullable();
            $table->unsignedSmallInteger('branch_count')->default(1);
            $table->json('services')->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('recruitment_reviewed_by')->nullable();
            $table->timestamp('recruitment_reviewed_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('order_no')->nullable();
            $table->string('fulfillment_type')->nullable();
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
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('partner_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->unsignedInteger('order_status_id')->nullable();
            $table->unsignedInteger('booking_status_id')->nullable();
            $table->timestamps();
        });

        City::query()->create(['name' => 'Davao City', 'active' => true]);
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

    public function test_agent_help_page_explains_enrollment_and_earnings(): void
    {
        $agent = $this->agent();
        $commissionTiers = config('agent.commission_tiers');

        $this->actingAs($agent, 'agent')->get(route('agent.help'))
            ->assertOk()
            ->assertSee('Help &amp; frequently asked questions', false)
            ->assertSee('How do I register a restaurant?')
            ->assertSee('Restaurant approval guide')
            ->assertSee('Save document review')
            ->assertSee('How is my commission calculated?')
            ->assertSee('How do commission tiers work?')
            ->assertSee('Approval determines the count')
            ->assertSee('Recorded commissions stay unchanged')
            ->assertSee('Can my tier go down?')
            ->assertSee('data-agent-tier-styles', false)
            ->assertSee('agent-tier-card is-current', false)
            ->assertSee('Contact agent support')
            ->assertSee('mailto:info@pahatud.com?subject=Agent%20support%20request', false)
            ->assertSee(route('agent.restaurants.create'));

        $this->assertSame($commissionTiers, config('agent.commission_tiers'));
    }

    public function test_admin_can_create_an_agent_and_email_a_temporary_password(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);

        $this->post(route('dashboard.agents.store'), [
            'name' => 'New Agent',
            'email' => 'new-agent@example.com',
            'mobile' => '09171234567',
            'city' => 'Davao City',
        ])->assertRedirect(route('dashboard.agents.index'));

        $agent = Agent::query()->where('email', 'new-agent@example.com')->firstOrFail();
        $this->assertTrue($agent->active);
        $this->assertTrue($agent->must_change_password);
        $this->assertNotNull($agent->temporary_password_created_at);
        $this->assertSame(number_format(config('agent.commission_tiers.0'), 2, '.', ''), $agent->commission_percentage);
        $this->assertSame((float) config('agent.commission_tiers.0'), $agent->commissionPercentage());

        Mail::assertSent(AgentTemporaryPasswordMail::class, function ($mail) use ($agent) {
            $this->assertTrue(Hash::check($mail->temporaryPassword, $agent->password));
            $this->assertStringContainsString('0–34 approved restaurants', $mail->render());

            return $mail->hasTo('new-agent@example.com');
        });
    }

    public function test_agent_commission_rate_cannot_be_overridden_manually(): void
    {
        $agent = $this->agent('automatic-tier@example.com');

        $this->artisan('agent:set-rate', [
            'email' => $agent->email,
            'percentage' => 99,
        ])->expectsOutputToContain('Agent rates are assigned automatically')
            ->assertExitCode(1);

        $this->assertSame('30.00', $agent->fresh()->commission_percentage);
        $this->assertSame((float) config('agent.commission_tiers.0'), $agent->commissionPercentage());
    }

    public function test_agent_registration_page_explains_the_commission_tiers(): void
    {
        City::query()->create(['name' => 'Inactive City', 'active' => false]);

        $this->get(route('agent.register'))
            ->assertOk()
            ->assertSee('<option value="Davao City"', false)
            ->assertDontSee('<option value="Inactive City"', false)
            ->assertSee('0–34')
            ->assertSee('35–49')
            ->assertSee('50 or more')
            ->assertSee('What does “approved restaurant” mean for my tier?', false)
            ->assertSee('Can I try the Merchant Dashboard?')
            ->assertSee('m@pahatud.com')
            ->assertSee('letmein')
            ->assertSee(route('merchant.login'), false)
            ->assertSee('Existing commission entries keep the rate originally recorded');
    }

    public function test_agent_can_register_only_in_an_active_city(): void
    {
        Mail::fake();
        City::query()->create(['name' => 'Inactive City', 'active' => false]);

        $application = [
            'name' => 'City Agent',
            'email' => 'city-agent@example.com',
            'mobile' => '09171234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'terms' => '1',
        ];

        $this->post(route('agent.register.store'), $application + ['city' => 'Inactive City'])
            ->assertSessionHasErrors('city');
        $this->assertDatabaseMissing('agents', ['email' => $application['email']]);

        $this->post(route('agent.register.store'), $application + ['city' => 'Davao City'])
            ->assertRedirect(route('agent.register.success'));
        $this->assertDatabaseHas('agents', [
            'email' => $application['email'],
            'city' => 'Davao City',
        ]);
        Mail::assertSent(AgentRegistrationReceivedMail::class);
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
            ->assertSee('Add new agent')
            ->assertDontSee('name="commission_percentage"', false);
    }

    public function test_admin_can_view_each_agents_details_and_restaurants(): void
    {
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent('profile@example.com');
        $restaurant = $this->restaurant($agent, 'Profile Restaurant');
        $admin = User::query()->forceCreate([
            'name' => 'Admin User', 'email' => 'admin-profile@example.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->setAttribute('firstname', 'Admin');
        $admin->setAttribute('lastname', 'User');

        $this->actingAs($admin)->get(route('dashboard.agents.index'))
            ->assertOk()
            ->assertSee(route('dashboard.agents.show', $agent));

        $this->get(route('dashboard.agents.show', $agent))
            ->assertOk()
            ->assertSee($agent->email)
            ->assertSee($restaurant->restaurant_name)
            ->assertSee(route('dashboard.merchant.application.show', $restaurant->id));
    }

    public function test_admin_can_approve_an_application_with_a_message(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent('approval@example.com');
        $agent->update(['active' => false]);

        $this->post(route('dashboard.agents.approve', $agent), [
            'message' => 'Welcome to the team.',
        ])->assertSessionHas('success');

        $agent->refresh();
        $this->assertTrue($agent->active);
        $this->assertSame('approved', $agent->review_status);
        $this->assertSame('Welcome to the team.', $agent->review_message);
        $this->assertNotNull($agent->reviewed_at);
        Mail::assertSent(AgentApprovedMail::class, fn ($mail) => $mail->hasTo($agent->email)
            && $mail->adminMessage === 'Welcome to the team.');
    }

    public function test_agent_email_includes_the_inline_pahatud_logo(): void
    {
        $agent = new Agent([
            'name' => 'Preview Agent',
            'email' => 'preview@example.com',
            'commission_percentage' => 30,
        ]);

        Mail::mailer('array')->to($agent->email)->send(new AgentRegistrationReceivedMail($agent));

        $message = Mail::mailer('array')->getSymfonyTransport()->messages()[0]->getOriginalMessage();
        $html = $message->getHtmlBody();
        $this->assertStringContainsString('cid:pahatud-logo@pahatud', $html);
        $this->assertMatchesRegularExpression(
            '/<td class="header"[^>]*>\s*<a[^>]*><img[^>]+><\/a>\s*<\/td>/s',
            $html,
        );
        $this->assertStringNotContainsString('letter-spacing: 2px', $html);
        $this->assertCount(1, $message->getAttachments());
        $this->assertSame('pahatud-logo@pahatud', $message->getAttachments()[0]->getContentId());
    }

    public function test_admin_can_decline_an_application_and_cannot_review_it_again(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent('declined@example.com');
        $agent->update(['active' => false]);

        $this->post(route('dashboard.agents.decline', $agent), [
            'message' => 'Please provide more details.',
        ])->assertSessionHas('success');

        $agent->refresh();
        $this->assertFalse($agent->active);
        $this->assertSame('declined', $agent->review_status);
        $this->assertSame('Please provide more details.', $agent->review_message);
        Mail::assertSent(AgentDeclinedMail::class, fn ($mail) => $mail->hasTo($agent->email)
            && $mail->adminMessage === 'Please provide more details.');

        $this->post(route('dashboard.agents.approve', $agent))->assertSessionHasErrors('review');
        $this->assertFalse($agent->fresh()->active);
        Mail::assertSentCount(1);
    }

    public function test_admin_can_view_the_agent_commission_report_with_cart_order_numbers(): void
    {
        $agent = $this->agent('commission-report@example.com');
        $restaurant = $this->restaurant($agent, 'Commission Report Restaurant', 'commission-restaurant@example.com');
        $cart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-AGENT-0042',
            'fulfillment_type' => Cart::FULFILLMENT_DELIVERY,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'qty' => 1,
            'price' => 100,
            'variance_total' => 0,
            'price_comm_total' => 12,
            'variance_total_comm_total' => 3,
            'discount_amount' => 0,
        ]);
        $order = Orders::query()->create([
            'cart_id' => $cart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        AgentCommission::query()->create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 125,
            'subtotal_amount' => 100,
            'delivery_fee_amount' => 25,
            'discount_amount' => 0,
            'total_amount' => 125,
            'pahatud_commission_percentage' => 20,
            'pahatud_commission_amount' => 20,
            'commission_percentage' => 30,
            'commission_amount' => 6,
            'status' => AgentCommission::STATUS_APPROVED,
            'qualified_at' => now(),
        ]);
        $order->updateQuietly(['order_status_id' => LibraryStatus::STATUS_DELIVERED]);

        $completedCart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-COMPLETED-0043',
            'fulfillment_type' => Cart::FULFILLMENT_PICKUP,
        ]);
        $completedOrder = Orders::query()->create([
            'cart_id' => $completedCart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        AgentCommission::query()->create([
            'order_id' => $completedOrder->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 50,
            'commission_percentage' => 30,
            'commission_amount' => 3,
            'status' => AgentCommission::STATUS_APPROVED,
            'qualified_at' => now(),
        ]);
        $completedOrder->updateQuietly(['order_status_id' => LibraryStatus::STATUS_COMPLETED]);

        $legacyDeliveryCart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-DELIVERED-0045',
        ]);
        $legacyDeliveryOrder = Orders::query()->create([
            'cart_id' => $legacyDeliveryCart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        AgentCommission::query()->create([
            'order_id' => $legacyDeliveryOrder->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 60,
            'commission_percentage' => 30,
            'commission_amount' => 3.60,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);
        $legacyDeliveryOrder->updateQuietly([
            'order_status_id' => null,
            'booking_status_id' => BookingStatus::STATUS_BOOKING_DELIVERED,
            'delivered_at' => now(),
        ]);

        $dineInCart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-DINEIN-0046',
            'fulfillment_type' => Cart::FULFILLMENT_DINE_IN,
        ]);
        $dineInOrder = Orders::query()->create([
            'cart_id' => $dineInCart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        AgentCommission::query()->create([
            'order_id' => $dineInOrder->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 80,
            'commission_percentage' => 30,
            'commission_amount' => 4.80,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);
        $dineInOrder->updateQuietly(['order_status_id' => LibraryStatus::STATUS_COMPLETED]);

        $processingCart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-PROCESSING-0044',
        ]);
        $processingOrder = Orders::query()->create([
            'cart_id' => $processingCart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_PROCESSING,
        ]);
        AgentCommission::query()->create([
            'order_id' => $processingOrder->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 75,
            'commission_percentage' => 30,
            'commission_amount' => 4.50,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);
        $admin = User::query()->forceCreate([
            'name' => 'Report Admin',
            'email' => 'report-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->withoutMiddleware(isAdmin::class);

        $this->actingAs($admin)
            ->get(route('dashboard.report.agents'))
            ->assertOk()
            ->assertSeeText('Agent Commission Report')
            ->assertSeeText('Commission Report Restaurant')
            ->assertSeeText('4 entries')
            ->assertSeeText('Order ID #'.$order->id)
            ->assertSeeText('Order number: PAH-AGENT-0042')
            ->assertSeeText('Order ID #'.$completedOrder->id)
            ->assertSeeText('Order number: PAH-COMPLETED-0043')
            ->assertSeeText('Order ID #'.$legacyDeliveryOrder->id)
            ->assertSeeText('Order number: PAH-DELIVERED-0045')
            ->assertSeeText('Order ID #'.$dineInOrder->id)
            ->assertSeeText('Order number: PAH-DINEIN-0046')
            ->assertSeeText('Order type: Delivery')
            ->assertSeeText('Order type: Pickup')
            ->assertSeeText('Order type: Dine-in')
            ->assertDontSeeText('Order ID #'.$processingOrder->id)
            ->assertDontSeeText('Order number: PAH-PROCESSING-0044')
            ->assertSeeText('Subtotal')
            ->assertSeeText('Delivery fee')
            ->assertSeeText('₱125.00')
            ->assertSeeText('₱4.50');
    }

    public function test_agent_statement_permanently_assigns_each_commission_once_and_is_visible_only_to_its_agent(): void
    {
        $agent = $this->agent('statement-agent@example.com');
        $otherAgent = $this->agent('other-statement-agent@example.com');
        $restaurant = $this->restaurant($agent, 'Statement Restaurant', 'statement-restaurant@example.com');
        $cart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-ASOA-0001',
            'fulfillment_type' => Cart::FULFILLMENT_DELIVERY,
        ]);
        $order = Orders::query()->create([
            'cart_id' => $cart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        $commission = AgentCommission::query()->create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 125,
            'subtotal_amount' => 100,
            'delivery_fee_amount' => 25,
            'total_amount' => 125,
            'pahatud_commission_percentage' => 20,
            'pahatud_commission_amount' => 20,
            'commission_percentage' => 30,
            'commission_amount' => 6,
            'status' => AgentCommission::STATUS_APPROVED,
            'qualified_at' => now(),
        ]);
        $admin = User::query()->forceCreate([
            'name' => 'Statement Admin',
            'email' => 'statement-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->withoutMiddleware(isAdmin::class);

        $response = $this->actingAs($admin)->post(route('dashboard.agent-statements.store'), [
            'agent_id' => $agent->id,
            'commission_ids' => [$commission->id],
            'notes' => 'October commission statement',
        ]);

        $statement = AgentStatementAccount::query()->firstOrFail();
        $response->assertRedirect(route('dashboard.agent-statements.show', $statement));
        $this->assertSame(AgentStatementAccount::STATUS_DRAFT, $statement->status);
        $this->assertNull($statement->issued_at);
        $this->assertDatabaseHas('agent_statement_account_items', [
            'agent_statement_account_id' => $statement->id,
            'agent_commission_id' => $commission->id,
            'order_number' => 'PAH-ASOA-0001',
            'agent_commission_amount' => 6,
        ]);

        $this->actingAs($admin)->post(route('dashboard.agent-statements.store'), [
            'agent_id' => $agent->id,
            'commission_ids' => [$commission->id],
        ])->assertSessionHasErrors('commission_ids');
        $this->assertDatabaseCount('agent_statement_account_items', 1);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.statements.index'))
            ->assertOk()
            ->assertDontSeeText($statement->reference);
        $this->actingAs($agent, 'agent')
            ->get(route('agent.statements.show', $statement))
            ->assertNotFound();

        $this->actingAs($admin)
            ->post(route('dashboard.agent-statements.publish', $statement))
            ->assertRedirect(route('dashboard.agent-statements.show', $statement));

        $statement->refresh();
        $this->assertSame(AgentStatementAccount::STATUS_PUBLISHED, $statement->status);
        $this->assertNotNull($statement->issued_at);

        $this->actingAs($admin)
            ->post(route('dashboard.agent-statements.publish', $statement))
            ->assertSessionHasErrors('statement');
        $this->actingAs($admin)
            ->delete(route('dashboard.agent-statements.destroy', $statement))
            ->assertSessionHasErrors('statement');
        $this->assertDatabaseHas('agent_statement_accounts', ['id' => $statement->id]);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.statements.index'))
            ->assertOk()
            ->assertSeeText($statement->reference);
        $this->actingAs($agent, 'agent')
            ->get(route('agent.statements.show', $statement))
            ->assertOk()
            ->assertSeeText($statement->reference)
            ->assertSeeText('PAH-ASOA-0001')
            ->assertSeeText('₱6.00');

        $this->actingAs($otherAgent, 'agent')
            ->get(route('agent.statements.show', $statement))
            ->assertNotFound();
    }

    public function test_admin_can_delete_an_agent_statement_draft_and_reuse_its_commissions(): void
    {
        $agent = $this->agent('draft-delete-agent@example.com');
        $commission = AgentCommission::query()->create([
            'order_id' => 98765,
            'restaurant_id' => 98765,
            'agent_id' => $agent->id,
            'order_amount' => 100,
            'commission_percentage' => 15,
            'commission_amount' => 3,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);
        $admin = User::query()->forceCreate([
            'name' => 'Draft Delete Admin',
            'email' => 'draft-delete-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->withoutMiddleware(isAdmin::class);
        $payload = [
            'agent_id' => $agent->id,
            'commission_ids' => [$commission->id],
        ];

        $this->actingAs($admin)->post(route('dashboard.agent-statements.store'), $payload);
        $draft = AgentStatementAccount::query()->firstOrFail();

        $this->actingAs($admin)
            ->delete(route('dashboard.agent-statements.destroy', $draft))
            ->assertRedirect(route('dashboard.agent-statements.index'));

        $this->assertDatabaseMissing('agent_statement_accounts', ['id' => $draft->id]);
        $this->assertDatabaseMissing('agent_statement_account_items', ['agent_commission_id' => $commission->id]);

        $this->actingAs($admin)
            ->post(route('dashboard.agent-statements.store'), $payload)
            ->assertSessionDoesntHaveErrors();
        $this->assertDatabaseHas('agent_statement_account_items', ['agent_commission_id' => $commission->id]);
    }

    public function test_restaurant_pages_are_scoped_to_the_logged_in_agent(): void
    {
        $agent = $this->agent('one@example.com');
        $otherAgent = $this->agent('two@example.com');
        $own = $this->restaurant($agent, 'My Inasal', 'inasal@example.com');
        $other = $this->restaurant($otherAgent, 'Other Cafe', 'other@example.com');

        $response = $this->actingAs($agent, 'agent')->get(route('agent.restaurants.index'));

        $response->assertOk()
            ->assertSee($own->restaurant_name)
            ->assertSee('Tier reminder:')
            ->assertDontSee($other->restaurant_name);
    }

    public function test_enrollment_automatically_links_the_restaurant_to_the_agent(): void
    {
        Mail::fake();
        Storage::fake('local');
        $agent = $this->agent();

        $this->actingAs($agent, 'agent')->post(route('agent.restaurants.store'), array_merge($this->enrollmentDocuments(), [
            'restaurant_name' => 'Inasal House',
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'email' => 'inasal@example.com',
            'mobile' => '09171234567',
            'address' => '123 Test Street',
            'city' => 'Davao City',
            'description' => 'Local grilled favorites.',
        ]))->assertRedirect(route('agent.restaurants.index'));

        $this->assertDatabaseHas('partners', [
            'restaurant_name' => 'Inasal House',
            'agent_id' => $agent->id,
            'active' => false,
        ]);
        $this->assertDatabaseHas('users', ['email' => 'inasal@example.com', 'name' => 'Maria Santos']);
        $user = User::query()->where('email', 'inasal@example.com')->firstOrFail();
        $restaurant = Partners::query()->where('email', 'inasal@example.com')->firstOrFail();
        $this->assertSame($user->id, $restaurant->user_id);
        $this->assertCount(2, $restaurant->enrollmentDocuments);
        $this->assertEqualsCanonicalizing(
            RestaurantEnrollmentDocument::REQUIRED_TYPES,
            $restaurant->enrollmentDocuments->pluck('document_type')->all(),
        );
        foreach ($restaurant->enrollmentDocuments as $document) {
            $this->assertSame($document->document_type.'.pdf', $document->original_name);
            $this->assertSame('pending_verification', $document->status);
            Storage::disk('local')->assertExists($document->file_path);
        }
        $this->assertDatabaseHas('restaurant_invitations', [
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
            'email' => 'inasal@example.com',
            'accepted_at' => null,
        ]);
        Mail::assertSent(RestaurantInvitationMail::class, fn ($mail) => $mail->hasTo('inasal@example.com'));
    }

    public function test_enrollment_form_only_shows_the_simplified_documents_and_payout_textarea(): void
    {
        $agent = $this->agent();
        City::query()->updateOrCreate(['name' => 'Tagum City'], ['active' => true]);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.restaurants.create'))
            ->assertOk()
            ->assertSee('<select class="agent-input " id="city" name="city"', false)
            ->assertSee('<option value="Tagum City"', false)
            ->assertSee('Payout account name and account details')
            ->assertSee('name="payout_account_name"', false)
            ->assertSee('Valid government-issued ID')
            ->assertSee('Business registration certificate')
            ->assertDontSee('name="business_registration_number"', false)
            ->assertDontSee('Mayor’s / Business Permit')
            ->assertDontSee('BIR Certificate of Registration')
            ->assertDontSee('Sanitary Permit')
            ->assertDontSee('Proof of payout account');
    }

    public function test_enrollment_rejects_a_city_outside_the_active_service_areas(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent, 'agent')->post(route('agent.restaurants.store'), array_merge($this->enrollmentDocuments(), [
            'restaurant_name' => 'Outside Cafe',
            'firstname' => 'Maria',
            'lastname' => 'Santos',
            'email' => 'outside@example.com',
            'mobile' => '09171234567',
            'address' => '123 Test Street',
            'city' => 'Unlisted City',
        ]))->assertSessionHasErrors('city');

        $this->assertDatabaseMissing('partners', ['email' => 'outside@example.com']);
    }

    public function test_enrollment_allows_missing_documents_for_later_upload(): void
    {
        Mail::fake();
        $agent = $this->agent();
        $details = [
            'restaurant_name' => 'Document Cafe',
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'email' => 'documents@example.com',
            'mobile' => '09170000000',
            'address' => 'Davao City',
            'city' => 'Davao City',
            'business_structure' => 'sole_proprietorship',
            'enrolling_as' => 'owner',
            'registered_business_name' => 'Document Cafe',
            'tin' => '123-456-789',
            'business_registration_number' => 'DTI-123',
            'payout_account_name' => 'Ana Reyes',
        ];

        $this->actingAs($agent, 'agent')->post(route('agent.restaurants.store'), $details)
            ->assertRedirect(route('agent.restaurants.index'));
        $restaurant = Partners::query()->where('email', 'documents@example.com')->firstOrFail();
        $this->assertSame('pending_review', $restaurant->application_status);
        $this->assertCount(2, $restaurant->missingEnrollmentDocuments());
    }

    public function test_restaurant_account_can_upload_a_missing_document_and_see_its_status(): void
    {
        Mail::fake();
        Storage::fake('local');
        $agent = $this->agent();
        $contact = User::query()->forceCreate([
            'name' => 'Restaurant Contact',
            'email' => 'contact@example.com',
            'password' => Hash::make('password123'),
        ]);
        $restaurant = $this->restaurant($agent);
        $restaurant->forceFill(['user_id' => $contact->id, 'active' => false, 'application_status' => 'pending_review'])->save();

        $this->actingAs($contact)
            ->post(route('merchant.application.documents.store'), [
                'document_type' => 'business_registration',
                'document' => UploadedFile::fake()->create('registration.pdf', 100, 'application/pdf'),
            ])->assertSessionHas('success');

        $document = $restaurant->enrollmentDocuments()->firstOrFail();
        $this->assertSame('pending_verification', $document->status);
        Storage::disk('local')->assertExists($document->file_path);
        $this->get(route('merchant.application.show'))->assertOk()->assertSee('Pending Verification');
        Mail::assertSent(RestaurantApplicationStatusMail::class, 2);
    }

    public function test_restaurant_account_can_save_business_information_from_application_form(): void
    {
        Mail::fake();
        $contact = User::query()->forceCreate([
            'name' => 'Restaurant Contact', 'email' => 'merchant-form@example.com',
            'password' => Hash::make('password123'),
        ]);
        $restaurant = $this->restaurant($this->agent());
        $restaurant->forceFill(['user_id' => $contact->id, 'active' => false])->save();

        $this->actingAs($contact)->get(route('merchant.application.show'))
            ->assertOk()
            ->assertSee('restaurant-application.css')
            ->assertSee('Save business information');

        $this->put(route('merchant.application.update'), [
            'firstname' => 'New', 'lastname' => 'Contact',
            'restaurant_name' => 'Updated Cafe', 'registered_business_name' => 'Updated Cafe',
            'tin' => '123-456-789', 'business_registration_number' => 'DTI-123',
            'payout_account_name' => 'New Contact', 'email' => 'merchant-form@example.com',
            'mobile' => '09171234567', 'city' => 'Davao City', 'address' => 'Updated address',
            'business_structure' => 'sole_proprietorship', 'enrolling_as' => 'owner',
        ])->assertSessionHas('success');

        $this->assertSame('Updated Cafe', $restaurant->fresh()->restaurant_name);
        $this->assertSame('pending_review', $restaurant->fresh()->application_status);
    }

    public function test_merchant_help_desk_and_dashboard_documentation_are_available(): void
    {
        $contact = User::query()->forceCreate([
            'name' => 'Merchant Help User',
            'email' => 'merchant-help@example.com',
            'password' => Hash::make('password123'),
        ]);
        $restaurant = $this->restaurant($this->agent(), 'Help Desk Cafe', 'merchant-help@example.com');
        $restaurant->forceFill(['user_id' => $contact->id])->save();

        $this->actingAs($contact)
            ->get(route('merchant.help'))
            ->assertOk()
            ->assertSee('Help desk &amp; documentation', false)
            ->assertSee('valid government-issued ID')
            ->assertSee('business registration certificate')
            ->assertSee('info@pahatud.com')
            ->assertSeeInOrder([
                'Workspace',
                'Dashboard',
                'Products',
                'Orders',
                'Application &amp; documents',
                'Branches',
                'Category',
                'Profile',
                'Sales',
                'Support &amp; account',
                'Help &amp; documentation',
                'Logout',
            ], false)
            ->assertDontSee('nav-header', false);

        $this->get(route('merchant.dashboard.index'))
            ->assertOk()
            ->assertSee('Merchant documentation')
            ->assertSee(route('merchant.help'));
    }

    public function test_merchant_dashboard_guides_an_incomplete_store_through_setup(): void
    {
        $contact = User::query()->forceCreate([
            'name' => 'New Merchant',
            'email' => 'new-merchant@example.com',
            'password' => Hash::make('password123'),
        ]);
        $restaurant = $this->restaurant($this->agent(), 'New Merchant Cafe', 'new-merchant@example.com');
        $restaurant->forceFill(['user_id' => $contact->id, 'application_status' => 'pending_review'])->save();

        $this->actingAs($contact)
            ->get(route('merchant.dashboard.index'))
            ->assertOk()
            ->assertSee('Finish setting up your store')
            ->assertSee('<strong>0</strong> of 5 complete', false)
            ->assertSee('Upload your logo')
            ->assertSee('Upload your main banner')
            ->assertSee('Add a promotional banner')
            ->assertSee('Add your first products')
            ->assertSee('Complete your application and documents')
            ->assertSee(route('merchant.dashboard.settings').'#profile-banner', false)
            ->assertSee(route('merchant.dashboard.product', ['setup' => 'add-product']), false)
            ->assertSee(route('merchant.application.show'), false);
    }

    public function test_merchant_setup_banner_disappears_after_every_step_is_submitted(): void
    {
        Storage::fake('local');

        $contact = User::query()->forceCreate([
            'name' => 'Ready Merchant',
            'email' => 'ready-merchant@example.com',
            'password' => Hash::make('password123'),
        ]);
        $restaurant = $this->restaurant($this->agent(), 'Ready Merchant Cafe', 'ready-merchant@example.com');
        $restaurant->forceFill([
            'user_id' => $contact->id,
            'img' => 'logo.png',
            'banner' => 'banner.jpg',
            'application_status' => 'pending_review',
        ])->save();

        foreach (RestaurantEnrollmentDocument::REQUIRED_TYPES as $type) {
            $path = 'restaurant-enrollment/'.$restaurant->id.'/'.$type.'.pdf';
            Storage::disk('local')->put($path, 'document');
            $restaurant->enrollmentDocuments()->create([
                'document_type' => $type,
                'file_path' => $path,
                'original_name' => $type.'.pdf',
                'status' => 'pending_verification',
            ]);
        }

        PartnerPromotion::query()->create([
            'partner_id' => $restaurant->id,
            'name' => 'Opening offer',
            'image_path' => 'uploads/promotions/opening-offer.jpg',
            'approval_status' => PartnerPromotion::APPROVAL_PENDING,
        ]);
        $restaurant->products()->create(['title' => 'House specialty']);

        $this->actingAs($contact)
            ->get(route('merchant.dashboard.index'))
            ->assertOk()
            ->assertDontSee('Finish setting up your store');
    }

    public function test_agent_can_view_and_update_only_their_restaurant_application(): void
    {
        Mail::fake();
        $agent = $this->agent();
        $otherAgent = $this->agent('other-agent@example.com');
        $restaurant = $this->restaurant($agent);
        $restaurant->update(['business_structure' => 'sole_proprietorship', 'enrolling_as' => 'owner']);
        $contact = User::query()->forceCreate([
            'name' => 'Old Contact', 'email' => 'old-contact@example.com', 'password' => Hash::make('password123'),
        ]);
        $restaurant->forceFill(['user_id' => $contact->id])->save();

        $this->actingAs($otherAgent, 'agent')->get(route('agent.restaurants.show', $restaurant))->assertNotFound();
        $this->actingAs($agent, 'agent')->get(route('agent.restaurants.show', $restaurant))->assertOk()->assertSee($restaurant->restaurant_name);

        $this->put(route('agent.restaurants.update', $restaurant), [
            'restaurant_name' => 'Updated Restaurant', 'firstname' => 'New', 'lastname' => 'Contact',
            'email' => 'new-contact@example.com', 'mobile' => '09170000000', 'address' => 'Updated address',
            'city' => 'Davao City', 'business_structure' => 'sole_proprietorship', 'enrolling_as' => 'owner',
            'registered_business_name' => 'Updated Restaurant', 'tin' => '123-456-789',
            'business_registration_number' => 'DTI-123', 'payout_account_name' => 'New Contact',
        ])->assertSessionHas('success');

        $this->assertSame('Updated Restaurant', $restaurant->fresh()->restaurant_name);
        $this->assertSame('pending_review', $restaurant->fresh()->application_status);
        $this->assertSame('new-contact@example.com', $contact->fresh()->email);
    }

    public function test_admin_can_open_restaurant_application_from_numeric_merchant_list_link(): void
    {
        $this->withoutMiddleware(isAdmin::class);
        $restaurant = $this->restaurant($this->agent());
        $admin = User::query()->forceCreate([
            'name' => 'Restaurant Admin',
            'email' => 'review-admin@example.com', 'password' => Hash::make('password123'),
        ]);
        $admin->setAttribute('firstname', 'Restaurant');
        $admin->setAttribute('lastname', 'Admin');

        $this->actingAs($admin)->get('/data/dashboard/merchant/'.$restaurant->id.'/application')
            ->assertOk()
            ->assertSee($restaurant->restaurant_name)
            ->assertSee('How to approve this application')
            ->assertSee('Still blocking approval')
            ->assertSee('action="'.route('dashboard.merchant.application.review', $restaurant->id).'"', false);
    }

    public function test_document_rejection_requires_remarks_and_notifies_both_parties(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $path = UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf')->store('restaurant-enrollment/'.$restaurant->id, 'local');
        $document = $restaurant->enrollmentDocuments()->create([
            'document_type' => 'business_permit', 'file_path' => $path, 'original_name' => 'permit.pdf',
        ]);

        $this->post(route('dashboard.merchant.documents.review', [$restaurant->id, $document]), ['status' => 'rejected'])
            ->assertSessionHasErrors('remarks');
        $this->post(route('dashboard.merchant.documents.review', [$restaurant->id, $document]), [
            'status' => 'rejected', 'remarks' => 'Please upload the current permit.',
        ])->assertSessionHas('success');

        $this->assertSame('rejected', $document->fresh()->status);
        $this->assertSame('Please upload the current permit.', $document->fresh()->remarks);
        Mail::assertSent(RestaurantApplicationStatusMail::class, 2);
    }

    public function test_admin_can_decline_restaurant_application_with_remarks(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $admin = User::query()->forceCreate([
            'name' => 'Restaurant Admin', 'email' => 'restaurant-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($admin)->post(route('dashboard.merchant.application.review', $restaurant->id), [
            'decision' => 'declined', 'remarks' => 'Please correct the registered business name.',
        ])->assertSessionHas('success');

        $restaurant->refresh();
        $this->assertSame('declined', $restaurant->application_status);
        $this->assertEquals(0, $restaurant->active);
        $this->assertNull($restaurant->verified_at);
        Mail::assertSent(RestaurantApplicationStatusMail::class, 2);
    }

    public function test_expired_document_pauses_approved_application_and_notifies_both_parties(): void
    {
        Mail::fake();
        Storage::fake('local');
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $restaurant->forceFill(['application_status' => 'approved', 'active' => true, 'verified_at' => now()])->save();
        $path = UploadedFile::fake()->create('government-id.pdf', 100, 'application/pdf')->store('restaurant-enrollment/'.$restaurant->id, 'local');
        $document = $restaurant->enrollmentDocuments()->create([
            'document_type' => 'government_id', 'file_path' => $path, 'original_name' => 'government-id.pdf',
            'status' => 'approved', 'expires_at' => today()->subDay(),
        ]);

        Artisan::call('restaurants:expire-documents');

        $this->assertSame('expired', $document->fresh()->status);
        $this->assertSame('pending_review', $restaurant->fresh()->application_status);
        $this->assertEquals(0, $restaurant->fresh()->active);
        Mail::assertSent(RestaurantApplicationStatusMail::class, 2);
    }

    public function test_agent_restaurant_cannot_be_activated_or_verified_with_missing_documents(): void
    {
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $restaurant->update(['active' => false, 'verified_at' => null]);

        $this->postJson('/api/data/merchant/'.$restaurant->id.'/status/submit')->assertStatus(422);
        $this->postJson('/api/data/merchant/'.$restaurant->id.'/verify/submit')->assertStatus(422);
        $this->assertEquals(0, $restaurant->fresh()->active);
        $this->assertNull($restaurant->fresh()->verified_at);
    }

    public function test_admin_can_review_private_enrollment_documents(): void
    {
        Storage::fake('local');
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $path = UploadedFile::fake()->create('permit.pdf', 100, 'application/pdf')
            ->store('restaurant-enrollment/'.$restaurant->id, 'local');
        $document = $restaurant->enrollmentDocuments()->create([
            'document_type' => 'business_permit',
            'file_path' => $path,
            'original_name' => 'permit.pdf',
        ]);

        $this->get(route('dashboard.merchant.documents.index', $restaurant))->assertRedirect('/');

        $this->withoutMiddleware(isAdmin::class);
        $this->get(route('dashboard.merchant.documents.index', $restaurant))
            ->assertOk()
            ->assertJsonPath('documents.0.document_type', 'business_permit');
        $this->get(route('dashboard.merchant.documents.show', [$restaurant, $document]))->assertOk();
    }

    public function test_complete_agent_enrollment_can_be_activated_and_verified(): void
    {
        Mail::fake();
        Storage::fake('local');
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $restaurant->update(['active' => false, 'verified_at' => null, 'enrolling_as' => 'authorized_representative']);

        foreach ([...RestaurantEnrollmentDocument::REQUIRED_TYPES, 'authorization_document'] as $type) {
            $path = UploadedFile::fake()->create($type.'.pdf', 100, 'application/pdf')
                ->store('restaurant-enrollment/'.$restaurant->id, 'local');
            $restaurant->enrollmentDocuments()->create([
                'document_type' => $type,
                'file_path' => $path,
                'original_name' => $type.'.pdf',
            ]);
        }

        $admin = User::query()->forceCreate([
            'name' => 'Restaurant Admin',
            'email' => 'restaurant-admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($admin);
        $this->post(route('dashboard.merchant.application.review', $restaurant->id), ['decision' => 'approved'])
            ->assertSessionHasErrors('decision');

        foreach ($restaurant->enrollmentDocuments as $document) {
            $this->post(route('dashboard.merchant.documents.review', [$restaurant->id, $document]), [
                'status' => 'approved',
            ])->assertSessionHas('success');
        }
        $this->post(route('dashboard.merchant.application.review', $restaurant->id), ['decision' => 'approved'])
            ->assertSessionHas('success');

        $this->assertEquals(1, $restaurant->fresh()->active);
        $this->assertNotNull($restaurant->fresh()->verified_at);
        $this->assertSame('approved', $restaurant->fresh()->application_status);
    }

    public function test_restaurant_contact_can_complete_the_one_time_invitation(): void
    {
        Mail::fake();
        Storage::fake('local');
        $agent = $this->agent();

        $this->actingAs($agent, 'agent')->post(route('agent.restaurants.store'), array_merge($this->enrollmentDocuments(), [
            'restaurant_name' => 'Invitation Cafe',
            'firstname' => 'Ana',
            'lastname' => 'Reyes',
            'email' => 'invited@example.com',
            'mobile' => '09170000000',
            'address' => '123 Invitation Street',
            'city' => 'Davao City',
        ]))->assertRedirect(route('agent.restaurants.index'));

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
            ->assertSee('agent-commission-table', false)
            ->assertSee('agent-order-breakdown', false)
            ->assertSee('Own Restaurant')
            ->assertSee('Why rates can differ between rows:')
            ->assertDontSee('Hidden Restaurant')
            ->assertSee('₱30.00')
            ->assertDontSee('₱60.00');
    }

    public function test_recent_commission_activity_displays_the_cart_order_number(): void
    {
        $agent = $this->agent('activity@example.com');
        $restaurant = $this->restaurant($agent, 'Activity Restaurant', 'activity-restaurant@example.com');
        $cart = Cart::query()->forceCreate([
            'partner_id' => $restaurant->id,
            'order_no' => 'PAH-2026-0042',
        ]);
        $order = Orders::query()->create([
            'cart_id' => $cart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
        ]);

        AgentCommission::query()->create([
            'order_id' => $order->id,
            'restaurant_id' => $restaurant->id,
            'agent_id' => $agent->id,
            'order_amount' => 100,
            'commission_percentage' => 30,
            'commission_amount' => 30,
            'status' => AgentCommission::STATUS_PENDING,
            'qualified_at' => now(),
        ]);

        $this->actingAs($agent, 'agent')
            ->get(route('agent.dashboard'))
            ->assertOk()
            ->assertSee('agent-commission-table', false)
            ->assertSee('agent-order-breakdown', false)
            ->assertSeeText('#PAH-2026-0042');
    }

    public function test_agent_can_track_and_approve_a_referred_partner_application(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $agent = $this->agent('referral-tracking@example.com');
        $application = Partners::query()->create([
            'agent_id' => $agent->id,
            'restaurant_name' => 'Referral Cafe',
            'registered_business_name' => 'Referral Cafe Foods',
            'owner_name' => 'Rina Santos',
            'email' => 'referral-cafe@example.com',
            'mobile' => '09171234567',
            'address' => '123 Referral Street',
            'city' => 'Cebu City',
            'business_structure' => 'sole_proprietorship',
            'branch_count' => 1,
            'services' => ['delivery'],
            'slug' => 'referral-cafe',
            'search_string' => 'referral cafe',
            'percentage' => 20,
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'recruitment_status' => Partners::STATUS_PENDING,
            'recruitment_submitted_at' => now(),
        ]);

        $this->actingAs($agent, 'agent')->get(route('agent.dashboard'))
            ->assertOk()
            ->assertSee($agent->referral_code)
            ->assertSee($agent->referralUrl())
            ->assertSee('Copy code')
            ->assertSee('Share link')
            ->assertSee('Referral Cafe');

        $this->actingAs($agent, 'agent')->get(route('agent.restaurants.index'))
            ->assertOk()
            ->assertSee('Referral Cafe')
            ->assertSee($application->created_at->format('M d, Y'))
            ->assertSee('Pending');

        $this->assertSame(0, $agent->restaurants()->count());

        $this->post(route('dashboard.merchant-applications.approve', $application), [
            'message' => 'Continue to onboarding.',
        ])->assertSessionHas('success');

        $this->assertSame($agent->id, $application->fresh()->agent_id);
        $this->assertSame(Partners::STATUS_APPROVED, $application->fresh()->recruitment_status);
        $this->assertSame(1, $agent->restaurants()->count());
        $this->assertSame(0, $agent->approvedRestaurantCount());

        $directoryResponse = $this->actingAs($agent, 'agent')->get(route('agent.restaurants.index'));
        $directoryResponse->assertOk()
            ->assertSee('Merchant directory')
            ->assertSee('Recruitment')
            ->assertSee('Enrollment');
        $this->assertSame(1, substr_count($directoryResponse->getContent(), 'data-merchant-id="'.$application->id.'"'));

        $futurePartner = Partners::query()->create([
            'agent_id' => $agent->id,
            'restaurant_name' => 'Future Referral Store',
            'registered_business_name' => 'Future Referral Store Inc.',
            'owner_name' => 'Luis Reyes',
            'email' => 'future-referral@example.com',
            'mobile' => '09179876543',
            'address' => '456 Future Avenue',
            'city' => 'Davao City',
            'business_structure' => 'corporation',
            'branch_count' => 1,
            'services' => ['pickup'],
            'slug' => 'future-referral-store',
            'search_string' => 'future referral store',
            'percentage' => 20,
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'recruitment_status' => Partners::STATUS_APPROVED,
            'recruitment_reviewed_at' => now(),
            'recruitment_submitted_at' => now(),
        ]);

        $this->assertSame($agent->id, $futurePartner->agent_id);
    }

    public function test_agent_can_update_an_owned_merchant_application_and_resubmit_it_for_approval(): void
    {
        Storage::fake('local');
        $agent = $this->agent('application-editor@example.com');
        $otherAgent = $this->agent('other-application-editor@example.com');
        $originalSubmittedAt = now()->subDay();
        $application = Partners::query()->create([
            'agent_id' => $agent->id,
            'restaurant_name' => 'Old Cafe Name',
            'registered_business_name' => 'Old Cafe Company',
            'owner_name' => 'Old Owner',
            'email' => 'old-cafe@example.com',
            'mobile' => '09170000000',
            'telephone' => '1234567',
            'address' => 'Old Address',
            'city' => 'Old City',
            'business_structure' => 'sole_proprietorship',
            'enrolling_as' => 'owner',
            'tin' => '000-000-000',
            'payout_account_name' => 'Old Owner · Old Bank · 0000',
            'cuisine' => 'Cafe',
            'branch_count' => 1,
            'services' => ['pickup'],
            'website' => 'https://old-cafe.example.com',
            'description' => 'Old description',
            'slug' => 'old-cafe-name',
            'search_string' => 'old cafe name old city',
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'recruitment_status' => Partners::STATUS_DECLINED,
            'recruitment_review_message' => 'Please correct the business details.',
            'recruitment_reviewed_by' => 12,
            'recruitment_reviewed_at' => now()->subHours(12),
            'recruitment_submitted_at' => $originalSubmittedAt,
            'application_status' => 'pending_review',
        ]);

        $this->actingAs($otherAgent, 'agent')
            ->get(route('agent.merchant-applications.edit', $application))
            ->assertNotFound();

        $this->actingAs($agent, 'agent')
            ->get(route('agent.merchant-applications.edit', $application))
            ->assertOk()
            ->assertSee('Update merchant application')
            ->assertSee('Please correct the business details.')
            ->assertSee('name="payout_account_name"', false)
            ->assertSee('name="government_id"', false)
            ->assertSee('name="business_registration"', false)
            ->assertSee('Save and submit for approval');

        $response = $this->actingAs($agent, 'agent')
            ->put(route('agent.merchant-applications.update', $application), [
                'restaurant_name' => 'Corrected Cafe',
                'registered_business_name' => 'Corrected Cafe Foods Inc.',
                'owner_name' => 'Maria Corrected',
                'email' => 'corrected-cafe@example.com',
                'mobile' => '09171234567',
                'telephone' => null,
                'address' => '45 Corrected Street',
                'city' => 'Davao City',
                'business_structure' => 'corporation',
                'enrolling_as' => 'authorized_representative',
                'tin' => '123-456-789',
                'business_registration_number' => 'SEC-2026-100',
                'payout_account_name' => 'Corrected Cafe Foods Inc. · Test Bank · 123456',
                'cuisine' => 'Filipino',
                'branch_count' => 3,
                'services' => ['dine_in', 'delivery'],
                'website' => 'https://corrected-cafe.example.com',
                'description' => 'Corrected application details.',
                'government_id' => UploadedFile::fake()->create('government-id.pdf', 100, 'application/pdf'),
                'business_registration' => UploadedFile::fake()->create('business-registration.pdf', 100, 'application/pdf'),
                'authorization_document' => UploadedFile::fake()->create('authorization.pdf', 100, 'application/pdf'),
            ]);

        $response->assertRedirect(route('agent.merchant-applications.edit', $application));
        $response->assertSessionHas('success', 'The merchant application was updated and submitted as Pending Approval.');

        $application->refresh();
        $this->assertSame('Corrected Cafe', $application->restaurant_name);
        $this->assertSame('corrected-cafe@example.com', $application->email);
        $this->assertSame(['dine_in', 'delivery'], $application->services);
        $this->assertSame('123-456-789', $application->tin);
        $this->assertSame('Corrected Cafe Foods Inc. · Test Bank · 123456', $application->payout_account_name);
        $this->assertSame('corrected cafe davao city', $application->search_string);
        $this->assertSame(Partners::STATUS_PENDING, $application->recruitment_status);
        $this->assertNull($application->recruitment_review_message);
        $this->assertNull($application->recruitment_reviewed_by);
        $this->assertNull($application->recruitment_reviewed_at);
        $this->assertTrue($application->recruitment_submitted_at->isAfter($originalSubmittedAt));
        $this->assertSame('pending_review', $application->application_status);
        $this->assertSame(3, $application->enrollmentDocuments()->count());

        foreach ([...RestaurantEnrollmentDocument::REQUIRED_TYPES, 'authorization_document'] as $type) {
            $document = $application->enrollmentDocuments()->where('document_type', $type)->firstOrFail();
            $this->assertSame('pending_verification', $document->status);
            Storage::disk('local')->assertExists($document->file_path);
        }

        $this->actingAs($agent, 'agent')
            ->get(route('agent.restaurants.index'))
            ->assertOk()
            ->assertSee('Corrected Cafe')
            ->assertSee('Pending Approval')
            ->assertSee(route('agent.merchant-applications.edit', $application));
    }

    public function test_agent_cannot_update_another_agents_merchant_application(): void
    {
        $owner = $this->agent('application-owner@example.com');
        $otherAgent = $this->agent('application-intruder@example.com');
        $application = Partners::query()->create([
            'agent_id' => $owner->id,
            'restaurant_name' => 'Protected Cafe',
            'registered_business_name' => 'Protected Cafe Company',
            'owner_name' => 'Protected Owner',
            'email' => 'protected-cafe@example.com',
            'mobile' => '09170000000',
            'address' => 'Protected Address',
            'city' => 'Davao City',
            'business_structure' => 'sole_proprietorship',
            'branch_count' => 1,
            'services' => ['pickup'],
            'slug' => 'protected-cafe',
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'recruitment_status' => Partners::STATUS_PENDING,
            'recruitment_submitted_at' => now(),
        ]);

        $this->actingAs($otherAgent, 'agent')
            ->put(route('agent.merchant-applications.update', $application), [
                'restaurant_name' => 'Hijacked Cafe',
            ])
            ->assertNotFound();

        $this->assertSame('Protected Cafe', $application->fresh()->restaurant_name);
    }

    public function test_delivered_order_snapshots_commission_and_cancellation_reverses_it(): void
    {
        config()->set('agent.pahatud_commission_percentage', 15);

        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $restaurant->update(['percentage' => 20]);
        $cart = Cart::query()->create(['partner_id' => $restaurant->id, 'delivery_fee' => 25, 'discount_amount' => 10]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'qty' => 1,
            'price' => 100,
            'variance_total' => 0,
            'price_comm_total' => 12,
            'variance_total_comm_total' => 3,
            'discount_amount' => 0,
        ]);
        $order = Orders::query()->create([
            'cart_id' => $cart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);

        $order->update(['order_status_id' => LibraryStatus::STATUS_DELIVERED, 'delivered_at' => now()]);

        $this->assertDatabaseHas('agent_commissions', [
            'order_id' => $order->id,
            'agent_id' => $agent->id,
            'order_amount' => 115,
            'subtotal_amount' => 100,
            'delivery_fee_amount' => 25,
            'discount_amount' => 10,
            'total_amount' => 115,
            'pahatud_commission_percentage' => 20,
            'pahatud_commission_amount' => 15,
            'commission_percentage' => config('agent.commission_tiers.0'),
            'commission_amount' => round(15 * config('agent.commission_tiers.0') / 100, 2),
            'status' => AgentCommission::STATUS_PENDING,
        ]);

        $agent->update(['commission_percentage' => 40]);
        $order->touch();
        $this->assertSame(number_format(config('agent.commission_tiers.0'), 2, '.', ''), $order->agentCommission()->first()->commission_percentage);

        $order->update(['order_status_id' => LibraryStatus::STATUS_CANCELLED]);
        $this->assertDatabaseHas('agent_commissions', [
            'order_id' => $order->id,
            'status' => AgentCommission::STATUS_REVERSED,
        ]);
    }

    public function test_pickup_and_dine_in_orders_snapshot_five_percent_commission(): void
    {
        config()->set('checkout.non_delivery_commission_percentage', 5);

        $agent = $this->agent();
        $restaurant = $this->restaurant($agent);
        $restaurant->update(['percentage' => 20]);

        foreach ([Cart::FULFILLMENT_PICKUP, Cart::FULFILLMENT_DINE_IN] as $fulfillmentType) {
            $cart = Cart::query()->create([
                'partner_id' => $restaurant->id,
                'fulfillment_type' => $fulfillmentType,
                'delivery_fee' => 0,
                'discount_amount' => 0,
            ]);
            CartItem::query()->create([
                'cart_id' => $cart->id,
                'qty' => 2,
                'price' => 100,
                'variance_total' => 20,
                'price_comm_total' => 12,
                'variance_total_comm_total' => 3,
                'discount_amount' => 0,
            ]);
            $order = Orders::query()->create([
                'cart_id' => $cart->id,
                'partner_id' => $restaurant->id,
                'submitted_at' => now(),
                'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
            ]);

            $order->update(['order_status_id' => LibraryStatus::STATUS_COMPLETED]);

            $this->assertDatabaseHas('agent_commissions', [
                'order_id' => $order->id,
                'subtotal_amount' => 240,
                'pahatud_commission_percentage' => 5,
                'pahatud_commission_amount' => 12,
                'commission_percentage' => config('agent.commission_tiers.0'),
                'commission_amount' => round(12 * config('agent.commission_tiers.0') / 100, 2),
            ]);
        }
    }

    public function test_commission_tiers_count_only_approved_restaurants_and_apply_to_future_network_orders(): void
    {
        $agent = $this->agent('tiered-agent@example.com');
        $orderRestaurant = $this->restaurant($agent, 'Original Restaurant', 'original-tier@example.com');

        for ($number = 1; $number <= 34; $number++) {
            $this->restaurant($agent, 'Approved Restaurant '.$number, "approved-{$number}@example.com")
                ->update(['application_status' => 'approved']);
        }

        $this->restaurant($agent, 'Pending Restaurant', 'pending-tier@example.com')
            ->update(['application_status' => 'pending_review']);
        $this->restaurant($agent, 'Declined Restaurant', 'declined-tier@example.com')
            ->update(['application_status' => 'declined']);

        $this->assertSame(34, $agent->approvedRestaurantCount());
        $this->assertSame((float) config('agent.commission_tiers.0'), $agent->commissionPercentage());

        $this->restaurant($agent, 'Approved Restaurant 35', 'approved-35@example.com')
            ->update(['application_status' => 'approved']);

        $this->assertSame(35, $agent->approvedRestaurantCount());
        $this->assertSame((float) config('agent.commission_tiers.35'), $agent->commissionPercentage());

        $firstTierOrder = $this->deliveredOrder($orderRestaurant, 100);
        $this->assertDatabaseHas('agent_commissions', [
            'order_id' => $firstTierOrder->id,
            'commission_percentage' => config('agent.commission_tiers.35'),
            'commission_amount' => round(config('agent.pahatud_commission_percentage') * config('agent.commission_tiers.35') / 100, 2),
        ]);

        for ($number = 36; $number <= 50; $number++) {
            $this->restaurant($agent, 'Approved Restaurant '.$number, "approved-{$number}@example.com")
                ->update(['application_status' => 'approved']);
        }

        $this->assertSame(50, $agent->approvedRestaurantCount());
        $this->assertSame((float) config('agent.commission_tiers.50'), $agent->commissionPercentage());

        $highestTierOrder = $this->deliveredOrder($orderRestaurant, 100);
        $this->assertDatabaseHas('agent_commissions', [
            'order_id' => $highestTierOrder->id,
            'commission_percentage' => config('agent.commission_tiers.50'),
            'commission_amount' => round(config('agent.pahatud_commission_percentage') * config('agent.commission_tiers.50') / 100, 2),
        ]);
        $this->assertSame(number_format(config('agent.commission_tiers.35'), 2, '.', ''), $firstTierOrder->agentCommission()->first()->commission_percentage);
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

    private function enrollmentDocuments(string $enrollingAs = 'owner'): array
    {
        $documents = [
            'business_structure' => 'sole_proprietorship',
            'enrolling_as' => $enrollingAs,
            'registered_business_name' => 'Test Restaurant LLC',
            'tin' => '123-456-789',
            'business_registration_number' => 'DTI-123',
            'payout_account_name' => 'Test Owner',
        ];

        foreach (RestaurantEnrollmentDocument::REQUIRED_TYPES as $type) {
            $documents[$type] = UploadedFile::fake()->create($type.'.pdf', 100, 'application/pdf');
        }

        if ($enrollingAs === 'authorized_representative') {
            $documents['authorization_document'] = UploadedFile::fake()->create('authorization.pdf', 100, 'application/pdf');
        }

        return $documents;
    }

    private function restaurant(Agent $agent, string $name = 'Test Restaurant', string $email = 'restaurant@example.com'): Partners
    {
        return $agent->restaurants()->create([
            'restaurant_name' => $name,
            'email' => $email,
            'mobile' => '09171234567',
            'address' => 'Davao City',
            'city' => 'Davao City',
            'registered_business_name' => $name,
            'tin' => '123-456-789',
            'business_registration_number' => 'DTI-123',
            'payout_account_name' => 'Test Owner',
            'business_structure' => 'sole_proprietorship',
            'enrolling_as' => 'owner',
            'slug' => str($name)->slug(),
            'search_string' => $name,
            'percentage' => config('agent.pahatud_commission_percentage'),
            'active' => true,
            'verified_at' => now(),
        ]);
    }

    private function deliveredOrder(Partners $restaurant, float $subtotal): Orders
    {
        $cart = Cart::query()->create([
            'partner_id' => $restaurant->id,
            'delivery_fee' => 0,
            'discount_amount' => 0,
        ]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'qty' => 1,
            'price' => $subtotal,
            'variance_total' => 0,
            'price_comm_total' => $subtotal * ((float) config('agent.pahatud_commission_percentage') / 100),
            'variance_total_comm_total' => 0,
            'discount_amount' => 0,
        ]);
        $order = Orders::query()->create([
            'cart_id' => $cart->id,
            'partner_id' => $restaurant->id,
            'submitted_at' => now(),
            'order_status_id' => LibraryStatus::STATUS_ORDER_PLACED,
        ]);
        $order->update([
            'order_status_id' => LibraryStatus::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);

        return $order;
    }
}
