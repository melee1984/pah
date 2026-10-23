<?php

namespace Tests\Feature;

use App\Agent;
use App\Http\Middleware\isAdmin;
use App\Mail\MerchantApplicationReceivedMail;
use App\Mail\MerchantApplicationStatusMail;
use App\Partners;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MerchantApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable()->index();
            $table->string('restaurant_name');
            $table->string('registered_business_name')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('email')->nullable()->unique();
            $table->string('mobile', 30)->nullable();
            $table->string('telephone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('business_structure', 30)->nullable();
            $table->string('cuisine', 120)->nullable();
            $table->unsignedSmallInteger('branch_count')->default(1);
            $table->json('services')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('application_source', 40)->nullable()->index();
            $table->string('recruitment_status', 20)->nullable()->index();
            $table->text('recruitment_review_message')->nullable();
            $table->timestamp('recruitment_submitted_at')->nullable();
            $table->string('application_status', 30)->nullable();
            $table->text('application_remarks')->nullable();
            $table->unsignedBigInteger('recruitment_reviewed_by')->nullable();
            $table->timestamp('recruitment_reviewed_at')->nullable();
            $table->string('slug')->nullable();
            $table->text('search_string')->nullable();
            $table->boolean('active')->default(false);
            $table->unsignedInteger('account_type_id')->nullable();
            $table->boolean('addup')->default(false);
            $table->timestamps();
        });
    }

    public function test_merchant_recruitment_page_explains_services_commission_and_requirements(): void
    {
        $this->get(route('merchant.register'))
            ->assertOk()
            ->assertSee('Become a Merchant')
            ->assertSee('Dine-in')
            ->assertSee('Pickup')
            ->assertSee('Delivery')
            ->assertSee(number_format(config('agent.pahatud_commission_percentage'), 2).'%')
            ->assertSee('Business registration')
            ->assertSee('Payout details');
    }

    public function test_food_business_can_submit_a_merchant_application(): void
    {
        Mail::fake();

        $response = $this->post(route('merchant.register.submit'), $this->validApplication());

        $response->assertRedirect(route('merchant.register.success'));
        $this->assertDatabaseHas('partners', [
            'restaurant_name' => 'Kusina ni Pahatud',
            'email' => 'owner@example.com',
            'recruitment_status' => Partners::STATUS_PENDING,
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'percentage' => config('agent.pahatud_commission_percentage'),
        ]);

        $application = Partners::query()->firstOrFail();
        $this->assertSame(['dine_in', 'pickup', 'delivery'], $application->services);
        Mail::assertSent(MerchantApplicationReceivedMail::class, fn ($mail) => $mail->hasTo('owner@example.com')
            && str_contains($mail->render(), 'Kusina ni Pahatud'));
    }

    public function test_agent_referral_link_prefills_and_attributes_a_merchant_application(): void
    {
        Mail::fake();
        $agent = Agent::query()->create([
            'name' => 'Referral Agent',
            'email' => 'referral-agent@example.com',
            'password' => 'password123',
            'commission_percentage' => 15,
            'active' => true,
        ]);
        $secondAgent = Agent::query()->create([
            'name' => 'Second Referral Agent',
            'email' => 'second-referral-agent@example.com',
            'password' => 'password123',
            'commission_percentage' => 15,
            'active' => true,
        ]);

        $this->assertNotNull($agent->referral_code);
        $this->assertNotSame($agent->referral_code, $secondAgent->referral_code);
        $this->assertStringContainsString('ref='.$agent->referral_code, $agent->referralUrl());

        $this->get($agent->referralUrl())
            ->assertOk()
            ->assertSee('Agent referral applied')
            ->assertSee($agent->name)
            ->assertSee('value="'.$agent->referral_code.'"', false);

        $this->post(route('merchant.register.submit'), [
            ...$this->validApplication(),
            'referral_code' => strtolower($agent->referral_code),
        ])->assertRedirect(route('merchant.register.success'));

        $this->assertDatabaseHas('partners', [
            'email' => 'owner@example.com',
            'agent_id' => $agent->id,
        ]);
    }

    public function test_invalid_or_inactive_agent_referral_code_is_rejected(): void
    {
        $inactiveAgent = Agent::query()->create([
            'name' => 'Inactive Agent',
            'email' => 'inactive-referral@example.com',
            'password' => 'password123',
            'commission_percentage' => 15,
            'active' => false,
        ]);

        $this->post(route('merchant.register.submit'), [
            ...$this->validApplication(),
            'referral_code' => $inactiveAgent->referral_code,
        ])->assertSessionHasErrors('referral_code');

        $this->assertDatabaseCount('partners', 0);
    }

    public function test_application_requires_at_least_one_service(): void
    {
        $payload = $this->validApplication();
        unset($payload['services']);

        $this->from(route('merchant.register'))
            ->post(route('merchant.register.submit'), $payload)
            ->assertRedirect(route('merchant.register'))
            ->assertSessionHasErrors('services');

        $this->assertDatabaseCount('partners', 0);
    }

    public function test_admin_can_approve_and_notify_a_merchant_applicant(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $admin = User::query()->forceCreate([
            'name' => 'Approval Admin',
            'email' => 'approval-admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $application = Partners::query()->create($this->modelAttributes());

        $response = $this->actingAs($admin)->post(route('dashboard.merchant-applications.approve', $application), [
            'message' => 'Our onboarding team will contact you next.',
        ]);
        $response->assertRedirect(route('dashboard.merchant-applications.index'))
            ->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(Partners::STATUS_APPROVED, $application->recruitment_status);
        $this->assertSame('Our onboarding team will contact you next.', $application->recruitment_review_message);
        $this->assertNotNull($application->recruitment_reviewed_at);
        $this->assertSame('pending_review', $application->application_status);
        $this->assertFalse((bool) $application->active);
        Mail::assertSent(MerchantApplicationStatusMail::class, fn ($mail) => $mail->hasTo($application->email)
            && $mail->application->recruitment_status === Partners::STATUS_APPROVED
            && str_contains($mail->render(), 'has been approved'));

        $this->get(route('dashboard.merchant-applications.index'))
            ->assertOk()
            ->assertDontSee('data-application-id="'.$application->id.'"', false)
            ->assertSee('All-time applications')
            ->assertSee('Approved');

        $pendingApplication = Partners::query()->create($this->modelAttributes([
            'restaurant_name' => 'Still Pending Cafe',
            'email' => 'still-pending@example.com',
        ]));
        $merchantDirectoryIds = Partners::query()->visibleInMerchantDirectory()->pluck('id');
        $this->assertTrue($merchantDirectoryIds->contains($application->id));
        $this->assertFalse($merchantDirectoryIds->contains($pendingApplication->id));
    }

    public function test_admin_can_list_and_open_merchant_applications(): void
    {
        $this->withoutMiddleware(isAdmin::class);
        $admin = User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => 'merchant-admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $application = Partners::query()->create($this->modelAttributes());

        $this->actingAs($admin)
            ->get(route('dashboard.merchant-applications.index'))
            ->assertOk()
            ->assertSee($application->restaurant_name)
            ->assertSee(route('dashboard.merchant-applications.show', $application));

        $this->get(route('dashboard.merchant-applications.show', $application))
            ->assertOk()
            ->assertSee($application->registered_business_name)
            ->assertSee('Approve')
            ->assertSee('Decline');
    }

    public function test_admin_can_filter_merchant_applications_by_referring_agent(): void
    {
        $this->withoutMiddleware(isAdmin::class);
        $admin = User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => 'referral-admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $firstAgent = Agent::query()->create([
            'name' => 'First Agent',
            'email' => 'first-agent@example.com',
            'password' => 'password123',
            'commission_percentage' => 15,
            'active' => true,
        ]);
        $secondAgent = Agent::query()->create([
            'name' => 'Second Agent',
            'email' => 'second-agent@example.com',
            'password' => 'password123',
            'commission_percentage' => 15,
            'active' => true,
        ]);
        Partners::query()->create($this->modelAttributes([
            'agent_id' => $firstAgent->id,
            'restaurant_name' => 'First Referred Business',
            'email' => 'first-business@example.com',
        ]));
        Partners::query()->create($this->modelAttributes([
            'agent_id' => $secondAgent->id,
            'restaurant_name' => 'Second Referred Business',
            'email' => 'second-business@example.com',
        ]));

        $this->actingAs($admin)
            ->get(route('dashboard.merchant-applications.index', ['agent_id' => $firstAgent->id]))
            ->assertOk()
            ->assertSee('First Referred Business')
            ->assertDontSee('Second Referred Business')
            ->assertSee($firstAgent->referral_code);
    }

    public function test_decline_requires_a_reason_and_an_application_cannot_be_reviewed_twice(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $application = Partners::query()->create($this->modelAttributes([
            'email' => 'decline@example.com',
        ]));

        $this->post(route('dashboard.merchant-applications.decline', $application))
            ->assertSessionHasErrors('message');
        $this->assertSame(Partners::STATUS_PENDING, $application->fresh()->recruitment_status);

        $this->post(route('dashboard.merchant-applications.decline', $application), [
            'message' => 'The submitted business information could not be verified.',
        ])->assertSessionHas('success');

        $this->assertSame(Partners::STATUS_DECLINED, $application->fresh()->recruitment_status);
        Mail::assertSentCount(1);

        $this->post(route('dashboard.merchant-applications.approve', $application))
            ->assertSessionHasErrors('review');
        Mail::assertSentCount(1);
    }

    public function test_legacy_applications_are_migrated_to_partners_before_the_old_table_is_removed(): void
    {
        Schema::create('merchant_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('business_name');
            $table->string('registered_business_name');
            $table->string('owner_name');
            $table->string('email');
            $table->string('mobile', 30);
            $table->string('telephone', 30)->nullable();
            $table->text('address');
            $table->string('city', 120);
            $table->string('business_structure', 30);
            $table->string('cuisine', 120)->nullable();
            $table->unsignedSmallInteger('branch_count')->default(1);
            $table->json('services');
            $table->string('website')->nullable();
            $table->text('business_description')->nullable();
            $table->decimal('commission_percentage', 5, 2);
            $table->string('status', 20);
            $table->text('review_message')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        $legacy = $this->validApplication();
        DB::table('merchant_applications')->insert([
            ...collect($legacy)->except(['terms', 'services'])->all(),
            'services' => json_encode($legacy['services'], JSON_THROW_ON_ERROR),
            'commission_percentage' => 20,
            'status' => 'approved',
            'review_message' => 'Welcome to onboarding.',
            'reviewed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require database_path('migrations/2026_10_23_000000_consolidate_merchant_applications_into_partners.php');
        $migration->up();

        $this->assertFalse(Schema::hasTable('merchant_applications'));
        $this->assertDatabaseHas('partners', [
            'restaurant_name' => 'Kusina ni Pahatud',
            'owner_name' => 'Maria Santos',
            'email' => 'owner@example.com',
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'recruitment_status' => Partners::STATUS_APPROVED,
            'recruitment_review_message' => 'Welcome to onboarding.',
        ]);
        $this->assertSame(
            ['dine_in', 'pickup', 'delivery'],
            Partners::query()->where('email', 'owner@example.com')->firstOrFail()->services,
        );
        $this->assertNull(Partners::query()->where('email', 'owner@example.com')->value('application_status'));
    }

    private function validApplication(): array
    {
        return [
            'business_name' => 'Kusina ni Pahatud',
            'registered_business_name' => 'Kusina ni Pahatud Foods',
            'owner_name' => 'Maria Santos',
            'email' => 'owner@example.com',
            'mobile' => '09171234567',
            'telephone' => '032-123-4567',
            'address' => '123 Market Street',
            'city' => 'Cebu City',
            'business_structure' => 'sole_proprietorship',
            'cuisine' => 'Filipino',
            'branch_count' => 2,
            'services' => ['dine_in', 'pickup', 'delivery'],
            'website' => 'https://example.com/restaurant',
            'business_description' => 'A neighborhood Filipino restaurant.',
            'terms' => '1',
        ];
    }

    private function modelAttributes(array $overrides = []): array
    {
        $application = collect($this->validApplication())->except('terms')->all();

        return [...[
            'restaurant_name' => $application['business_name'],
            'registered_business_name' => $application['registered_business_name'],
            'owner_name' => $application['owner_name'],
            'email' => $application['email'],
            'mobile' => $application['mobile'],
            'telephone' => $application['telephone'],
            'address' => $application['address'],
            'city' => $application['city'],
            'business_structure' => $application['business_structure'],
            'cuisine' => $application['cuisine'],
            'branch_count' => $application['branch_count'],
            'services' => $application['services'],
            'website' => $application['website'],
            'description' => $application['business_description'],
            'percentage' => config('agent.pahatud_commission_percentage'),
            'application_source' => Partners::APPLICATION_SOURCE_REGISTRATION,
            'recruitment_status' => Partners::STATUS_PENDING,
            'recruitment_submitted_at' => now(),
            'slug' => 'kusina-ni-pahatud',
        ], ...$overrides];
    }
}
