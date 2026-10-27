<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.site_key' => 'test-site-key',
            'services.turnstile.secret' => 'test-secret-key',
            'services.turnstile.verify_url' => 'https://challenges.cloudflare.com/turnstile/v0/siteverify',
        ]);
    }

    public function test_public_form_requires_a_turnstile_token(): void
    {
        Http::fake();

        $this->from(route('contactus'))
            ->post(route('contact.submit'), $this->contactData())
            ->assertRedirect(route('contactus'))
            ->assertSessionHasErrors('cf-turnstile-response');

        Http::assertNothingSent();
    }

    public function test_valid_token_and_matching_action_allow_the_request(): void
    {
        Http::fake([
            config('services.turnstile.verify_url') => Http::response([
                'success' => true,
                'action' => 'contact',
                'hostname' => 'localhost',
                'error-codes' => [],
            ]),
        ]);

        $this->from(route('contactus'))
            ->post(route('contact.submit'), [
                ...$this->contactData(),
                'cf-turnstile-response' => 'valid-test-token',
            ])
            ->assertRedirect(route('contactus'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('display', 'alert-success');

        Http::assertSent(function (Request $request) {
            return $request->url() === config('services.turnstile.verify_url')
                && $request['secret'] === 'test-secret-key'
                && $request['response'] === 'valid-test-token'
                && $request['remoteip'] === '127.0.0.1';
        });
    }

    public function test_token_for_another_action_is_rejected(): void
    {
        Http::fake([
            config('services.turnstile.verify_url') => Http::response([
                'success' => true,
                'action' => 'customer_login',
                'hostname' => 'localhost',
                'error-codes' => [],
            ]),
        ]);

        $this->from(route('contactus'))
            ->post(route('contact.submit'), [
                ...$this->contactData(),
                'cf-turnstile-response' => 'wrong-action-token',
            ])
            ->assertRedirect(route('contactus'))
            ->assertSessionHasErrors('cf-turnstile-response');
    }

    public function test_api_forms_receive_json_validation_errors(): void
    {
        Http::fake();

        $this->postJson('/api/register/submit', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('cf-turnstile-response');

        Http::assertNothingSent();
    }

    public function test_widget_exposes_only_the_site_key(): void
    {
        $this->get(route('contactus'))
            ->assertOk()
            ->assertSee('https://challenges.cloudflare.com/turnstile/v0/api.js', false)
            ->assertSee('data-sitekey="test-site-key"', false)
            ->assertSee('data-action="contact"', false)
            ->assertDontSee('test-secret-key');
    }

    private function contactData(): array
    {
        return [
            'name' => 'Turnstile Tester',
            'email' => 'turnstile@example.com',
            'subject' => 'Security verification',
            'message' => 'Please verify this protected form submission.',
        ];
    }
}
