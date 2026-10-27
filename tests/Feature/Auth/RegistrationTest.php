<?php

namespace Tests\Feature\Auth;

use App\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        City::query()->create(['name' => 'Davao City', 'active' => true]);
        City::query()->create(['name' => 'Inactive City', 'active' => false]);

        $response = $this->get('/register');

        $response->assertStatus(200)
            ->assertSee('class="res-tab', false)
            ->assertSee('<option value="Davao City"', false)
            ->assertDontSee('<option value="Inactive City"', false);
    }

    public function test_registration_rejects_an_inactive_or_unknown_city(): void
    {
        City::query()->create(['name' => 'Davao City', 'active' => true]);
        City::query()->create(['name' => 'Inactive City', 'active' => false]);

        $registration = [
            'firstname' => 'Test',
            'lastname' => 'User',
            'email' => 'test@example.com',
            'mobile' => '09171234567',
            'password' => 'password',
        ];

        $this->post('/register', $registration + ['city' => 'Inactive City'])
            ->assertSessionHasErrors('city');
        $this->assertGuest();

        $this->post('/register', $registration + ['city' => 'Unknown City'])
            ->assertSessionHasErrors('city');
        $this->assertGuest();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
