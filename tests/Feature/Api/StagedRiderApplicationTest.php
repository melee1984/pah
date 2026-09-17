<?php

namespace Tests\Feature\Api;

use App\Mail\RiderApplicationSubmittedMail;
use App\RiderApplication;
use App\RiderApplicationDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class StagedRiderApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitting_a_complete_application_emails_the_rider(): void
    {
        Mail::fake();
        $token = Str::random(64);
        $application = RiderApplication::query()->create([
            'reference' => (string) Str::uuid(),
            'access_token_hash' => hash('sha256', $token),
            'full_name' => 'Carlo Juan',
            'email' => 'carlo@example.com',
            'mobile' => '09171234567',
            'password' => 'secret-password',
            'birth_date' => '1997-03-18',
            'home_address' => 'Lahug, Cebu City',
            'emergency_contact_name' => 'Maria Juan',
            'emergency_contact_relationship' => 'Sister',
            'emergency_contact_mobile' => '09185550188',
            'vehicle_type' => 'Motorcycle',
            'vehicle_make_model' => 'Honda Click 125i',
            'vehicle_plate_number' => '123 ABC',
            'vehicle_color' => 'Black',
            'status' => RiderApplication::STATUS_DRAFT,
        ]);

        foreach (RiderApplicationDocument::TYPES as $type) {
            $application->documents()->create([
                'reference' => (string) Str::uuid(),
                'type' => $type,
                'path' => "test/{$type}.jpg",
                'original_name' => "{$type}.jpg",
                'mime_type' => 'image/jpeg',
                'size_bytes' => 100,
            ]);
        }

        $this->withApplicationToken($token)
            ->postJson("/api/v1/rider/applications/{$application->reference}/submit")
            ->assertOk()
            ->assertJsonPath('application.status', RiderApplication::STATUS_PENDING);

        Mail::assertSent(RiderApplicationSubmittedMail::class, function (RiderApplicationSubmittedMail $mail) {
            return $mail->hasTo('carlo@example.com')
                && str_contains($mail->render(), 'submitted and is now under review');
        });
        Mail::assertSentCount(1);
    }

    public function test_rider_registration_starts_with_only_four_fields_and_uses_the_existing_application_steps(): void
    {
        Storage::fake('local');

        $registered = $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/v1/rider/auth/register', [
                'full_name' => 'Carlo Juan',
                'mobile' => '09171234567',
                'email' => 'CARLO@example.com',
                'password' => 'secret-password',
            ])
            ->assertCreated()
            ->assertJsonPath('application.status', 'draft')
            ->assertJsonPath('application.email', 'carlo@example.com')
            ->assertJsonPath('application.personal.full_name', 'Carlo Juan')
            ->assertJsonPath('application.personal.mobile', '09171234567')
            ->json();

        $applicationId = $registered['application']['id'];

        $this->withApplicationToken($registered['access_token'])
            ->patchJson("/api/v1/rider/applications/{$applicationId}/personal", [
                'birth_date' => '1997-03-18',
                'home_address' => 'Lahug, Cebu City',
            ])
            ->assertOk()
            ->assertJsonPath('application.personal.full_name', 'Carlo Juan')
            ->assertJsonPath('application.personal.mobile', '09171234567')
            ->assertJsonPath('application.personal.birth_date', '1997-03-18');

        $this->withApplicationToken($registered['access_token'])
            ->postJson("/api/v1/rider/applications/{$applicationId}/submit")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['emergency_contact_name', 'documents.profile_photo']);

        $this->withApplicationToken($registered['access_token'])
            ->patchJson("/api/v1/rider/applications/{$applicationId}/emergency-contact", [
                'name' => 'Maria Juan',
                'relationship' => 'Sister',
                'mobile' => '09185550188',
            ])
            ->assertOk();

        $this->withApplicationToken($registered['access_token'])
            ->patchJson("/api/v1/rider/applications/{$applicationId}/vehicle", [
                'type' => 'Motorcycle',
                'make_model' => 'Honda Click 125i',
                'plate_number' => '123 ABC',
                'color' => 'Black',
            ])
            ->assertOk();

        $this->withApplicationToken($registered['access_token'])
            ->patchJson("/api/v1/rider/applications/{$applicationId}/payout-account", [
                'method' => 'GCash',
                'account_name' => 'Carlo Juan',
                'account_number' => '09171234567',
            ])
            ->assertOk();

        foreach ($this->documents() as $type => $file) {
            $this->withApplicationToken($registered['access_token'])
                ->post("/api/v1/rider/applications/{$applicationId}/documents", [
                    'type' => $type,
                    'file' => $file,
                ])
                ->assertSuccessful();
        }

        $this->withApplicationToken($registered['access_token'])
            ->postJson("/api/v1/rider/applications/{$applicationId}/submit")
            ->assertOk()
            ->assertJsonPath('application.status', 'pending');

        $this->assertDatabaseHas('rider_applications', [
            'email' => 'carlo@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_rider_registration_validates_required_fields_and_duplicate_email(): void
    {
        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/v1/rider/auth/register', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['full_name', 'mobile', 'email', 'password']);

        $payload = [
            'full_name' => 'Carlo Juan',
            'mobile' => '09171234567',
            'email' => 'carlo@example.com',
            'password' => 'secret-password',
        ];

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/v1/rider/auth/register', $payload)
            ->assertCreated();

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/v1/rider/auth/register', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_a_rider_can_complete_and_submit_a_staged_application(): void
    {
        Storage::fake('local');

        $draft = $this->createDraft();
        $applicationId = $draft['application']['id'];
        $token = $draft['access_token'];

        $this->withApplicationToken($token)
            ->patchJson("/api/v1/rider/applications/{$applicationId}/personal", [
                'full_name' => 'Carlo Juan',
                'birth_date' => '1997-03-18',
                'home_address' => 'Lahug, Cebu City',
                'mobile' => '09171234567',
            ])
            ->assertOk()
            ->assertJsonPath('application.personal.full_name', 'Carlo Juan');

        $this->withApplicationToken($token)
            ->patchJson("/api/v1/rider/applications/{$applicationId}/emergency-contact", [
                'name' => 'Maria Juan',
                'relationship' => 'Sister',
                'mobile' => '09185550188',
            ])
            ->assertOk();

        $this->withApplicationToken($token)
            ->patchJson("/api/v1/rider/applications/{$applicationId}/vehicle", [
                'type' => 'Motorcycle',
                'make_model' => 'Honda Click 125i',
                'plate_number' => '123 abc',
                'color' => 'Black',
            ])
            ->assertOk()
            ->assertJsonPath('application.vehicle.plate_number', '123 ABC');

        $this->withApplicationToken($token)
            ->patchJson("/api/v1/rider/applications/{$applicationId}/payout-account", [
                'method' => 'GCash',
                'account_name' => 'Carlo Juan',
                'account_number' => '09171234567',
            ])
            ->assertOk()
            ->assertJsonPath('application.payout_account.masked_account_number', '•••••••4567');

        foreach ($this->documents() as $type => $file) {
            $this->withApplicationToken($token)
                ->post("/api/v1/rider/applications/{$applicationId}/documents", [
                    'type' => $type,
                    'file' => $file,
                ])
                ->assertSuccessful()
                ->assertJsonPath('document.type', $type);
        }

        $this->withApplicationToken($token)
            ->getJson('/api/v1/rider/applications/current')
            ->assertOk()
            ->assertJsonPath('application.status', 'draft')
            ->assertJsonCount(4, 'application.documents');

        $this->withApplicationToken($token)
            ->postJson("/api/v1/rider/applications/{$applicationId}/submit")
            ->assertOk()
            ->assertJsonPath('application.status', 'pending')
            ->assertJsonPath('application.progress.application_submitted', 'complete');

        $this->withApplicationToken($token)
            ->getJson("/api/v1/rider/applications/{$applicationId}/status")
            ->assertOk()
            ->assertJsonPath('application.status', 'pending');

        $application = RiderApplication::firstOrFail();

        $this->assertNotSame($token, $application->access_token_hash);
        $this->assertSame(hash('sha256', $token), $application->access_token_hash);
        $this->assertNotNull($application->submitted_at);
    }

    public function test_an_incomplete_draft_cannot_be_submitted(): void
    {
        $draft = $this->createDraft();

        $this->withApplicationToken($draft['access_token'])
            ->postJson("/api/v1/rider/applications/{$draft['application']['id']}/submit")
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'birth_date',
                'home_address',
                'documents.profile_photo',
                'documents.government_id',
                'documents.drivers_license',
                'documents.vehicle_registration',
            ]);

        $this->assertDatabaseHas('rider_applications', [
            'status' => 'draft',
        ]);
    }

    public function test_application_routes_require_the_application_bearer_token(): void
    {
        $draft = $this->createDraft();

        $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->getJson("/api/v1/rider/applications/{$draft['application']['id']}")
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid or missing rider application token.');
    }

    public function test_an_approved_application_can_activate_a_rider_account(): void
    {
        $draft = $this->createDraft();
        $application = RiderApplication::firstOrFail();
        $application->update([
            'full_name' => 'Carlo Juan',
            'mobile' => '09171234567',
            'status' => RiderApplication::STATUS_APPROVED,
        ]);
        $challengeId = (string) Str::uuid();
        DB::table('rider_api_otp_challenges')->insert([
            'reference' => $challengeId,
            'purpose' => 'activation',
            'channel' => 'email',
            'destination' => 'carlo@example.com',
            'code_hash' => hash('sha256', '123456'),
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->withApplicationToken($draft['access_token'])
            ->postJson("/api/v1/rider/applications/{$application->reference}/activation/confirm", [
                'challenge_id' => $challengeId,
                'code' => '123456',
            ])
            ->assertCreated()
            ->assertJsonPath('account_status', 'approved');

        $this->assertDatabaseHas('users', ['email' => 'carlo@example.com']);
        $this->assertDatabaseHas('rider', [
            'name' => 'Carlo Juan',
            'mobile' => '09171234567',
            'active' => true,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function createDraft(): array
    {
        return $this->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/v1/rider/auth/register', [
                'full_name' => 'Carlo Juan',
                'mobile' => '09171234567',
                'email' => 'carlo@example.com',
                'password' => 'secret-password',
            ])
            ->assertCreated()
            ->assertJsonPath('application.status', 'draft')
            ->assertJsonStructure([
                'application' => ['id', 'status', 'email', 'progress'],
                'access_token',
                'token_type',
            ])
            ->json();
    }

    /**
     * @return array<string, UploadedFile>
     */
    private function documents(): array
    {
        return [
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
            'government_id' => UploadedFile::fake()->image('government-id.jpg'),
            'drivers_license' => UploadedFile::fake()->image('drivers-license.jpg'),
            'vehicle_registration' => UploadedFile::fake()->create(
                'vehicle-registration.pdf',
                100,
                'application/pdf',
            ),
        ];
    }

    private function withApplicationToken(string $token): static
    {
        return $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-Admin-Request' => 'apiRequestHandle001',
        ]);
    }
}
