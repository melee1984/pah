<?php

namespace Tests\Unit;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\Partners;
use App\PartnerLocation;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MerchantDeviceIdTest extends TestCase
{
    public function test_it_updates_the_authenticated_merchant_device_id(): void
    {
        $user = new class extends User
        {
            public bool $wasSaved = false;

            public function save(array $options = []): bool
            {
                $this->wasSaved = true;

                return true;
            }
        };

        $request = Request::create('/api/merchant/device-id', 'PUT', [
            'device_id' => 'merchant-device-123',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->updateDeviceId($request);

        $this->assertTrue($user->wasSaved);
        $this->assertSame('merchant-device-123', $user->device_id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([
            'status' => 1,
            'message' => 'Device ID updated successfully.',
            'device_id' => 'merchant-device-123',
        ], $response->getData(true));
    }

    public function test_device_id_is_required(): void
    {
        $request = Request::create('/api/merchant/device-id', 'PUT');
        $request->setUserResolver(fn () => new User);

        $this->expectException(ValidationException::class);

        (new OrderController)->updateDeviceId($request);
    }

    public function test_it_updates_a_device_token_on_the_merchants_location(): void
    {
        $user = new User;
        $merchant = new Partners;
        $merchant->id = 12;
        $user->setRelation('merchant', $merchant);

        $location = new class extends PartnerLocation
        {
            public bool $wasSaved = false;

            public function save(array $options = []): bool
            {
                $this->wasSaved = true;

                return true;
            }
        };
        $location->id = 45;
        $location->partner_id = 12;

        $request = Request::create('/api/merchant/location/45/device-token', 'POST', [
            'device_token' => 'location-token-456',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->updateLocationDeviceToken($location, $request);

        $this->assertTrue($location->wasSaved);
        $this->assertSame('location-token-456', $location->device_token);
        $this->assertSame([
            'status' => 1,
            'message' => 'Merchant location device token updated successfully.',
            'location_id' => 45,
            'device_token' => 'location-token-456',
        ], $response->getData(true));
    }

    public function test_it_updates_the_merchants_location_without_a_device_token(): void
    {
        $user = new User;
        $merchant = new Partners;
        $merchant->id = 12;
        $user->setRelation('merchant', $merchant);

        $location = new class extends PartnerLocation
        {
            public bool $wasSaved = false;

            public function save(array $options = []): bool
            {
                $this->wasSaved = true;

                return true;
            }
        };
        $location->id = 45;
        $location->partner_id = 12;
        $location->device_token = 'existing-token';

        $request = Request::create('/api/merchant/location/45/device-token', 'POST', [
            'latitude' => 8.4542,
            'longtitude' => 124.6319,
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->updateLocationDeviceToken($location, $request);

        $this->assertTrue($location->wasSaved);
        $this->assertSame(8.4542, $location->latitude);
        $this->assertSame(124.6319, $location->longtitude);
        $this->assertSame('existing-token', $location->device_token);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_it_does_not_update_another_merchants_location(): void
    {
        $user = new User;
        $merchant = new Partners;
        $merchant->id = 12;
        $user->setRelation('merchant', $merchant);

        $location = new PartnerLocation;
        $location->id = 45;
        $location->partner_id = 99;

        $request = Request::create('/api/merchant/location/45/device-token', 'POST', [
            'device_token' => 'location-token-456',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->updateLocationDeviceToken($location, $request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'status' => 0,
            'message' => 'Merchant location not found.',
        ], $response->getData(true));
        $this->assertNull($location->device_token);
    }

    public function test_it_does_not_update_a_location_when_the_user_has_no_merchant(): void
    {
        $user = new User;
        $user->setRelation('merchant', null);

        $location = new PartnerLocation;
        $location->id = 45;
        $location->partner_id = 12;

        $request = Request::create('/api/merchant/location/45/device-token', 'POST', [
            'device_token' => 'location-token-456',
        ]);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->updateLocationDeviceToken($location, $request);

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame([
            'status' => 0,
            'message' => 'Merchant location not found.',
        ], $response->getData(true));
        $this->assertNull($location->device_token);
    }
}
