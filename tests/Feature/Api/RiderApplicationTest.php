<?php

namespace Tests\Feature\Api;

use App\RiderApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RiderApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_rider_can_submit_an_application(): void
    {
        Storage::fake('local');

        $response = $this
            ->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->post('/api/rider/account/register', $this->validPayload());

        $response
            ->assertCreated()
            ->assertJsonPath('status', 1)
            ->assertJsonPath('application.status', 'pending')
            ->assertJsonPath('application.progress.application_submitted', 'complete')
            ->assertJsonStructure([
                'message',
                'application' => ['reference', 'status', 'submitted_at', 'progress'],
            ]);

        $application = RiderApplication::firstOrFail();

        $this->assertTrue(Hash::check('secret-password', $application->password));
        $this->assertSame('09171234567', $application->payout_account_number);
        $this->assertNotSame('09171234567', $application->getRawOriginal('payout_account_number'));
        $this->assertSame('123 ABC', $application->vehicle_plate_number);

        Storage::disk('local')->assertExists($application->profile_photo_path);
        Storage::disk('local')->assertExists($application->government_id_path);
        Storage::disk('local')->assertExists($application->drivers_license_path);
        Storage::disk('local')->assertExists($application->vehicle_registration_path);
    }

    public function test_required_registration_fields_are_validated(): void
    {
        Storage::fake('local');

        $response = $this
            ->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->postJson('/api/rider/register/submit', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'full_name',
                'email',
                'mobile',
                'password',
                'birth_date',
                'home_address',
                'profile_photo',
                'emergency_contact_name',
                'emergency_contact_relationship',
                'emergency_contact_mobile',
                'government_id',
                'drivers_license',
                'vehicle_registration',
                'vehicle_type',
                'vehicle_make_model',
                'vehicle_plate_number',
                'vehicle_color',
                'payout_method',
                'payout_account_name',
                'payout_account_number',
            ]);

        $this->assertDatabaseCount('rider_applications', 0);
    }

    public function test_an_email_cannot_have_multiple_rider_applications(): void
    {
        Storage::fake('local');

        $this
            ->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->post('/api/rider/register/submit', $this->validPayload())
            ->assertCreated();

        $this
            ->withHeader('X-Admin-Request', 'apiRequestHandle001')
            ->post('/api/rider/register/submit', $this->validPayload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('rider_applications', 1);
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(): array
    {
        return [
            'full_name' => 'Carlo Juan',
            'email' => 'carlo@example.com',
            'mobile' => '09171234567',
            'password' => 'secret-password',
            'password_confirmation' => 'secret-password',
            'birth_date' => '1997-03-18',
            'home_address' => 'Lahug, Cebu City',
            'profile_photo' => UploadedFile::fake()->image('profile.jpg'),
            'emergency_contact_name' => 'Maria Juan',
            'emergency_contact_relationship' => 'Sister',
            'emergency_contact_mobile' => '09185550188',
            'government_id' => UploadedFile::fake()->image('government-id.jpg'),
            'drivers_license' => UploadedFile::fake()->image('drivers-license.jpg'),
            'vehicle_registration' => UploadedFile::fake()->create('vehicle-registration.pdf', 100, 'application/pdf'),
            'vehicle_type' => 'Motorcycle',
            'vehicle_make_model' => 'Honda Click 125i',
            'vehicle_plate_number' => '123 abc',
            'vehicle_color' => 'Black',
            'payout_method' => 'GCash',
            'payout_account_name' => 'Carlo Juan',
            'payout_account_number' => '09171234567',
        ];
    }
}
