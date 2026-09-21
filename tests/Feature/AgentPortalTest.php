<?php

namespace Tests\Feature;

use App\Agent;
use App\AgentCommission;
use App\Http\Middleware\isAdmin;
use App\LibraryStatus;
use App\Mail\AgentApprovedMail;
use App\Mail\AgentDeclinedMail;
use App\Mail\AgentRegistrationReceivedMail;
use App\Mail\AgentTemporaryPasswordMail;
use App\Mail\RestaurantApplicationStatusMail;
use App\Mail\RestaurantInvitationMail;
use App\Model\Cart;
use App\Model\CartItem;
use App\Model\Orders\Orders;
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

        // Production adds these fields with database/sql/agent_application_review.sql.
        Schema::table('agents', function (Blueprint $table) {
            $table->string('review_status', 20)->nullable()->index();
            $table->text('review_message')->nullable();
            $table->timestamp('reviewed_at')->nullable();
        });

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
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();
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

        $this->actingAs($agent, 'agent')->get(route('agent.help'))
            ->assertOk()
            ->assertSee('Help &amp; frequently asked questions', false)
            ->assertSee('How do I register a restaurant?')
            ->assertSee('Restaurant approval guide')
            ->assertSee('Save document review')
            ->assertSee('How is my commission calculated?')
            ->assertSee('Contact agent support')
            ->assertSee('mailto:info@pahatud.com?subject=Agent%20support%20request', false)
            ->assertSee(route('agent.restaurants.create'));
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
        $this->assertStringContainsString('cid:pahatud-logo@pahatud', $message->getHtmlBody());
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
            ->assertSeeText('Order #PAH-AGENT-0042')
            ->assertSeeText('Subtotal')
            ->assertSeeText('Delivery fee')
            ->assertSeeText('₱125.00')
            ->assertSeeText('₱6.00');
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
        foreach ($restaurant->enrollmentDocuments as $document) {
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

        $this->actingAs($agent, 'agent')
            ->get(route('agent.restaurants.create'))
            ->assertOk()
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
                'Help &amp; documentation',
                'Logout',
            ], false)
            ->assertDontSee('nav-header', false);

        $this->get(route('merchant.dashboard.index'))
            ->assertOk()
            ->assertSee('Merchant documentation')
            ->assertSee(route('merchant.help'));
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
            'price_comm_total' => 0,
            'variance_total_comm_total' => 0,
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
            'pahatud_commission_amount' => 20,
            'commission_percentage' => 30,
            'commission_amount' => 6,
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
}
