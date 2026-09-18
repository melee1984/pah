<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Mobile\Store\OrderController;
use App\PartnerLocation;
use App\Partners;
use App\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MerchantDeviceTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('device_token')->nullable();
            $table->timestamps();
        });

        PartnerLocation::query()->create(['partner_id' => 12, 'device_token' => 'old-branch-1']);
        PartnerLocation::query()->create(['partner_id' => 12, 'device_token' => 'old-branch-2']);
        PartnerLocation::query()->create(['partner_id' => 99, 'device_token' => 'other-merchant']);
    }

    public function test_token_is_saved_only_on_the_selected_branch(): void
    {
        $response = (new OrderController)->savePartnerDeviceToken($this->merchantRequest(1, 'new-branch-1'));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(1, $response->getData(true)['location_id']);
        $this->assertSame('new-branch-1', PartnerLocation::find(1)->device_token);
        $this->assertSame('old-branch-2', PartnerLocation::find(2)->device_token);
        $this->assertSame('other-merchant', PartnerLocation::find(3)->device_token);
    }

    public function test_token_cannot_be_saved_on_another_merchants_branch(): void
    {
        $response = (new OrderController)->savePartnerDeviceToken($this->merchantRequest(3, 'stolen-token'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('other-merchant', PartnerLocation::find(3)->device_token);
    }

    public function test_legacy_token_endpoint_also_saves_to_the_selected_branch(): void
    {
        $request = Request::create('/api/merchant/token/submit', 'POST', [
            'merchant_location_id' => 2,
            'token' => 'legacy-branch-token',
        ]);
        $user = $this->merchantUser();
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->saveTokenDeviceStore($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('legacy-branch-token', PartnerLocation::find(2)->device_token);
        $this->assertSame('old-branch-1', PartnerLocation::find(1)->device_token);
        $this->assertNull($user->device_token_store);
    }

    public function test_location_and_token_are_required(): void
    {
        $request = Request::create('/api/merchant/device-token', 'POST');
        $request->setUserResolver(fn () => $this->merchantUser());

        try {
            (new OrderController)->savePartnerDeviceToken($request);
            $this->fail('Expected validation to reject the missing location and token.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('merchant_location_id', $exception->errors());
            $this->assertArrayHasKey('device_token_store', $exception->errors());
        }
    }

    public function test_non_merchant_cannot_save_a_branch_token(): void
    {
        $request = $this->merchantRequest(1, 'new-token');
        $user = new User;
        $user->setRelation('merchant', null);
        $request->setUserResolver(fn () => $user);

        $response = (new OrderController)->savePartnerDeviceToken($request);

        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('old-branch-1', PartnerLocation::find(1)->device_token);
    }

    private function merchantRequest(int $locationId, string $token): Request
    {
        $request = Request::create('/api/merchant/device-token', 'POST', [
            'merchant_location_id' => $locationId,
            'device_token_store' => $token,
        ]);
        $request->setUserResolver(fn () => $this->merchantUser());

        return $request;
    }

    private function merchantUser(): User
    {
        $user = new User;
        $merchant = new Partners;
        $merchant->id = 12;
        $user->setRelation('merchant', $merchant);

        return $user;
    }
}
