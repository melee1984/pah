<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\User\AccessController;
use App\User;
use Illuminate\Http\Request;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    public function test_logout_revokes_the_authenticated_users_api_token(): void
    {
        $user = new class extends User
        {
            public bool $wasSaved = false;

            public function save(array $options = [])
            {
                $this->wasSaved = true;

                return true;
            }
        };
        $user->forceFill(['api_token' => 'active-token']);

        $request = Request::create('/api/account/logout', 'POST');
        $request->setUserResolver(fn () => $user);

        $response = (new AccessController)->postLogout($request);

        $this->assertNull($user->api_token);
        $this->assertTrue($user->wasSaved);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'status' => 1,
            'message' => 'User logged out successfully.',
        ], $response->getData(true));
    }
}
