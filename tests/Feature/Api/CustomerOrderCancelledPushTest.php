<?php

namespace Tests\Feature\Api;

use App\Exceptions\FirebasePushException;
use App\Jobs\SendCustomerOrderCancelledPush;
use App\LibraryStatus;
use App\Services\FirebaseMerchantOrderPush;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CustomerOrderCancelledPushTest extends TestCase
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

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('device_token_food')->nullable();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('cart', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id');
            $table->string('order_no');
        });
        Schema::create('order', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('order_status_id');
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 10, 'device_token_food' => 'customer-token']);
        DB::table('partners')->insert(['id' => 5]);
        DB::table('cart')->insert(['id' => 20, 'partner_id' => 5, 'order_no' => 'ORDER-20']);
        DB::table('order')->insert([
            'id' => 30,
            'user_id' => 10,
            'cart_id' => 20,
            'order_status_id' => LibraryStatus::STATUS_CANCELLED,
        ]);
    }

    public function test_job_targets_the_customer_who_placed_the_cancelled_order(): void
    {
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldReceive('sendCustomerCancelled')
            ->once()
            ->with('customer-token', 'ORDER-20', 30);

        (new SendCustomerOrderCancelledPush(30))->handle($push);
    }

    public function test_job_skips_an_order_that_is_not_cancelled(): void
    {
        DB::table('order')->where('id', 30)->update([
            'order_status_id' => LibraryStatus::STATUS_PROCESSING,
        ]);
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldNotReceive('sendCustomerCancelled');

        (new SendCustomerOrderCancelledPush(30))->handle($push);
    }

    public function test_job_removes_an_unregistered_customer_token_without_retrying(): void
    {
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldReceive('sendCustomerCancelled')->once()->andThrow(new FirebasePushException(
            404,
            'UNREGISTERED',
            'Requested entity was not found.',
            'customer order',
        ));

        (new SendCustomerOrderCancelledPush(30))->handle($push);

        $this->assertDatabaseHas('users', [
            'id' => 10,
            'device_token_food' => null,
        ]);
    }

    public function test_firebase_message_identifies_the_merchant_cancellation(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $privateKey);
        $path = tempnam(sys_get_temp_dir(), 'customer-cancel-push-');
        file_put_contents($path, json_encode([
            'project_id' => 'customer-cancel-push-test',
            'client_email' => 'test@example.com',
            'private_key' => $privateKey,
        ]));
        config(['services.firebase.service_account' => $path]);
        Cache::forget('firebase-messaging-token:customer-cancel-push-test');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/customer-cancel-push-test/messages/1']),
        ]);

        try {
            (new FirebaseMerchantOrderPush)->sendCustomerCancelled('customer-token', 'ORDER-20', 30);

            Http::assertSent(fn ($request) => str_contains($request->url(), '/messages:send')
                && $request['message']['token'] === 'customer-token'
                && $request['message']['notification'] === [
                    'title' => 'Order cancelled',
                    'body' => 'Your order ORDER-20 has been cancelled by the merchant.',
                ]
                && $request['message']['data'] === [
                    'type' => 'order_cancelled',
                    'order_id' => '30',
                    'order_no' => 'ORDER-20',
                ]);
        } finally {
            unlink($path);
            Cache::forget('firebase-messaging-token:customer-cancel-push-test');
        }
    }
}
