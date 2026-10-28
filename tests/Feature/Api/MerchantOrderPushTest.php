<?php

namespace Tests\Feature\Api;

use App\Events\SendPushNotificationEvent;
use App\Exceptions\FirebasePushException;
use App\Jobs\SendMerchantOrderPush;
use App\Model\Orders\Orders;
use App\Services\FirebaseMerchantOrderPush;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class MerchantOrderPushTest extends TestCase
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

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('device_token')->nullable();
            $table->timestamps();
        });

        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('partner_location_address_id');
            $table->string('order_no');
            $table->timestamps();
        });

        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('cart_id');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        DB::table('partner_location')->insert([
            ['id' => 10, 'partner_id' => 5, 'device_token' => 'branch-10-token'],
            ['id' => 11, 'partner_id' => 5, 'device_token' => 'branch-11-token'],
            ['id' => 12, 'partner_id' => 9, 'device_token' => 'another-merchant-token'],
        ]);
        DB::table('partners')->insert([['id' => 5], ['id' => 9]]);
    }

    public function test_new_order_event_queues_a_merchant_push(): void
    {
        Queue::fake();
        $orderId = $this->createOrder(10);

        event(new SendPushNotificationEvent(Orders::findOrFail($orderId)));

        Queue::assertPushed(SendMerchantOrderPush::class, fn ($job) => $job->orderId === $orderId);
    }

    public function test_job_sends_to_the_orders_branch_only(): void
    {
        $orderId = $this->createOrder(10);
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldReceive('send')
            ->once()
            ->with('branch-10-token', 'TEST-100', $orderId, 10);

        (new SendMerchantOrderPush($orderId))->handle($push);
    }

    public function test_job_skips_a_location_belonging_to_another_merchant(): void
    {
        $orderId = $this->createOrder(12);
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldNotReceive('send');

        (new SendMerchantOrderPush($orderId))->handle($push);
    }

    public function test_job_removes_an_unregistered_branch_token_without_retrying(): void
    {
        $orderId = $this->createOrder(10);
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldReceive('send')->once()->andThrow(new FirebasePushException(
            404,
            'UNREGISTERED',
            'Requested entity was not found.',
            'merchant order',
        ));

        (new SendMerchantOrderPush($orderId))->handle($push);

        $this->assertDatabaseHas('partner_location', [
            'id' => 10,
            'device_token' => null,
        ]);
    }

    public function test_firebase_message_includes_the_order_and_partner_location(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $privateKey);
        $path = tempnam(sys_get_temp_dir(), 'merchant-push-');
        file_put_contents($path, json_encode([
            'project_id' => 'merchant-push-test',
            'client_email' => 'test@example.com',
            'private_key' => $privateKey,
        ]));
        config(['services.firebase.service_account' => $path]);
        Cache::forget('firebase-messaging-token:merchant-push-test');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/merchant-push-test/messages/1']),
        ]);

        try {
            (new FirebaseMerchantOrderPush)->send('branch-10-token', 'TEST-100', 42, 10);

            Http::assertSent(fn ($request) => str_contains($request->url(), '/messages:send')
                && $request['message']['token'] === 'branch-10-token'
                && $request['message']['data'] === [
                    'type' => 'new_order',
                    'order_id' => '42',
                    'order_no' => 'TEST-100',
                    'partner_location_id' => '10',
                ]);
        } finally {
            unlink($path);
            Cache::forget('firebase-messaging-token:merchant-push-test');
        }
    }

    public function test_firebase_unregistered_response_exposes_the_provider_error_code(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $privateKey);
        $path = tempnam(sys_get_temp_dir(), 'merchant-push-error-');
        file_put_contents($path, json_encode([
            'project_id' => 'merchant-push-error-test',
            'client_email' => 'test@example.com',
            'private_key' => $privateKey,
        ]));
        config(['services.firebase.service_account' => $path]);
        Cache::forget('firebase-messaging-token:merchant-push-error-test');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'fcm.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 404,
                    'message' => 'Requested entity was not found.',
                    'status' => 'NOT_FOUND',
                    'details' => [[
                        '@type' => 'type.googleapis.com/google.firebase.fcm.v1.FcmError',
                        'errorCode' => 'UNREGISTERED',
                    ]],
                ],
            ], 404),
        ]);

        try {
            (new FirebaseMerchantOrderPush)->send('expired-token', 'TEST-100', 42, 10);
            $this->fail('An unregistered Firebase token should be rejected.');
        } catch (FirebasePushException $exception) {
            $this->assertSame(404, $exception->httpStatus);
            $this->assertSame('UNREGISTERED', $exception->firebaseErrorCode);
            $this->assertTrue($exception->hasInvalidToken());
            $this->assertStringContainsString('Requested entity was not found.', $exception->getMessage());
        } finally {
            unlink($path);
            Cache::forget('firebase-messaging-token:merchant-push-error-test');
        }
    }

    private function createOrder(int $locationId): int
    {
        $cartId = DB::table('cart')->insertGetId([
            'partner_id' => 5,
            'partner_location_address_id' => $locationId,
            'order_no' => 'TEST-100',
        ]);

        return DB::table('order')->insertGetId([
            'user_id' => 1,
            'partner_id' => 5,
            'cart_id' => $cartId,
            'submitted_at' => now(),
        ]);
    }
}
