<?php

namespace Tests\Feature\Admin;

use App\City;
use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('admin', 'web');
        Role::findOrCreate('manager', 'web');
    }

    public function test_administrator_can_manage_cities(): void
    {
        $admin = $this->user('admin@example.com');
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('dashboard.cities.index'))
            ->assertOk()
            ->assertSee('Cities');

        $this->actingAs($admin)
            ->post(route('dashboard.cities.store'), ['name' => 'Davao City'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $city = City::query()->where('name', 'Davao City')->firstOrFail();
        $this->assertTrue($city->active);

        $this->put(route('dashboard.cities.update', $city), [
            'name' => 'Davao',
            'active' => '0',
        ])->assertRedirect()->assertSessionHas('success');

        $city->refresh();
        $this->assertSame('Davao', $city->name);
        $this->assertFalse($city->active);

        $this->delete(route('dashboard.cities.destroy', $city))
            ->assertRedirect()
            ->assertSessionHas('success');
        $this->assertModelMissing($city);
    }

    public function test_city_manager_cannot_manage_cities(): void
    {
        $manager = $this->user('manager@example.com');
        $manager->assignRole('manager');

        $this->actingAs($manager)
            ->get(route('dashboard.cities.index'))
            ->assertForbidden();
    }

    private function user(string $email): User
    {
        return User::query()->forceCreate([
            'name' => strtok($email, '@'),
            'email' => $email,
            'password' => Hash::make('password'),
        ]);
    }
}
