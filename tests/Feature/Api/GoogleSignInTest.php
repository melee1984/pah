<?php

namespace Tests\Feature\Api;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GoogleSignInTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'services.google.client_ids' => ['mobile-client.apps.googleusercontent.com'],
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->string('mobile')->nullable();
            $table->string('password')->default('');
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->string('avatar')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('api_token')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('ip_address')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function test_mobile_google_endpoint_accepts_a_valid_id_token(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response($this->googleClaims()),
        ]);

        $response = $this->postJson('/api/mobile/account/google', [
            'id_token' => 'valid-google-id-token',
        ], [
            'X-Admin-Request' => 'apiRequestHandle001',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('email', 'google-user@example.com')
            ->assertJsonPath('is_new_user', true)
            ->assertJsonStructure(['access_token', 'session_id']);

        $this->assertDatabaseHas('users', [
            'email' => 'google-user@example.com',
            'provider' => 'google',
            'provider_id' => 'google-subject-123',
        ]);

        Http::assertSent(fn ($request) =>
            $request['id_token'] === 'valid-google-id-token'
        );
    }

    public function test_mobile_google_endpoint_rejects_a_token_for_another_client(): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response($this->googleClaims([
                'aud' => 'untrusted-client.apps.googleusercontent.com',
            ])),
        ]);

        $this->postJson('/api/mobile/account/google', [
            'id_token' => 'wrong-audience-token',
        ], [
            'X-Admin-Request' => 'apiRequestHandle001',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_mobile_google_endpoint_requires_an_id_token(): void
    {
        Http::fake();

        $this->postJson('/api/mobile/account/google', [], [
            'X-Admin-Request' => 'apiRequestHandle001',
        ])->assertUnprocessable();

        Http::assertNothingSent();
    }

    private function googleClaims(array $overrides = []): array
    {
        return array_merge([
            'iss' => 'https://accounts.google.com',
            'aud' => 'mobile-client.apps.googleusercontent.com',
            'sub' => 'google-subject-123',
            'email' => 'google-user@example.com',
            'email_verified' => 'true',
            'given_name' => 'Google',
            'family_name' => 'User',
            'picture' => 'https://example.com/avatar.jpg',
            'exp' => now()->addHour()->timestamp,
        ], $overrides);
    }
}
