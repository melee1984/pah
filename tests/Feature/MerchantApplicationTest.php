<?php

namespace Tests\Feature;

use App\Http\Middleware\isAdmin;
use App\Mail\MerchantApplicationReceivedMail;
use App\Mail\MerchantApplicationStatusMail;
use App\MerchantApplication;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MerchantApplicationTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertDatabaseHas('merchant_applications', [
            'business_name' => 'Kusina ni Pahatud',
            'email' => 'owner@example.com',
            'status' => MerchantApplication::STATUS_PENDING,
            'commission_percentage' => config('agent.pahatud_commission_percentage'),
        ]);

        $application = MerchantApplication::query()->firstOrFail();
        $this->assertSame(['dine_in', 'pickup', 'delivery'], $application->services);
        Mail::assertSent(MerchantApplicationReceivedMail::class, fn ($mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_application_requires_at_least_one_service(): void
    {
        $payload = $this->validApplication();
        unset($payload['services']);

        $this->from(route('merchant.register'))
            ->post(route('merchant.register.submit'), $payload)
            ->assertRedirect(route('merchant.register'))
            ->assertSessionHasErrors('services');

        $this->assertDatabaseCount('merchant_applications', 0);
    }

    public function test_admin_can_approve_and_notify_a_merchant_applicant(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $application = MerchantApplication::query()->create($this->modelAttributes());

        $this->post(route('dashboard.merchant-applications.approve', $application), [
            'message' => 'Our onboarding team will contact you next.',
        ])->assertSessionHas('success');

        $application->refresh();
        $this->assertSame(MerchantApplication::STATUS_APPROVED, $application->status);
        $this->assertSame('Our onboarding team will contact you next.', $application->review_message);
        $this->assertNotNull($application->reviewed_at);
        Mail::assertSent(MerchantApplicationStatusMail::class, fn ($mail) => $mail->hasTo($application->email)
            && $mail->application->status === MerchantApplication::STATUS_APPROVED);
    }

    public function test_admin_can_list_and_open_merchant_applications(): void
    {
        $this->withoutMiddleware(isAdmin::class);
        $admin = User::query()->forceCreate([
            'name' => 'Admin User',
            'email' => 'merchant-admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $application = MerchantApplication::query()->create($this->modelAttributes());

        $this->actingAs($admin)
            ->get(route('dashboard.merchant-applications.index'))
            ->assertOk()
            ->assertSee($application->business_name)
            ->assertSee(route('dashboard.merchant-applications.show', $application));

        $this->get(route('dashboard.merchant-applications.show', $application))
            ->assertOk()
            ->assertSee($application->registered_business_name)
            ->assertSee('Approve')
            ->assertSee('Decline');
    }

    public function test_decline_requires_a_reason_and_an_application_cannot_be_reviewed_twice(): void
    {
        Mail::fake();
        $this->withoutMiddleware(isAdmin::class);
        $application = MerchantApplication::query()->create($this->modelAttributes([
            'email' => 'decline@example.com',
        ]));

        $this->post(route('dashboard.merchant-applications.decline', $application))
            ->assertSessionHasErrors('message');
        $this->assertSame(MerchantApplication::STATUS_PENDING, $application->fresh()->status);

        $this->post(route('dashboard.merchant-applications.decline', $application), [
            'message' => 'The submitted business information could not be verified.',
        ])->assertSessionHas('success');

        $this->assertSame(MerchantApplication::STATUS_DECLINED, $application->fresh()->status);
        Mail::assertSentCount(1);

        $this->post(route('dashboard.merchant-applications.approve', $application))
            ->assertSessionHasErrors('review');
        Mail::assertSentCount(1);
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
        return [...collect($this->validApplication())->except('terms')->all(), ...$overrides, ...[
            'commission_percentage' => config('agent.pahatud_commission_percentage'),
            'status' => MerchantApplication::STATUS_PENDING,
        ]];
    }
}
