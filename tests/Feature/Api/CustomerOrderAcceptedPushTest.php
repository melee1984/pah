<?php

namespace Tests\Feature\Api;

use App\Jobs\SendCustomerOrderAcceptedPush;
use App\Services\FirebaseMerchantOrderPush;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CustomerOrderAcceptedPushTest extends TestCase
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
            $table->timestamp('store_accepted_at')->nullable();
            $table->timestamps();
        });

        DB::table('users')->insert(['id' => 10, 'device_token_food' => 'customer-token']);
        DB::table('partners')->insert(['id' => 5]);
        DB::table('cart')->insert(['id' => 20, 'partner_id' => 5, 'order_no' => 'ORDER-20']);
        DB::table('order')->insert([
            'id' => 30,
            'user_id' => 10,
            'cart_id' => 20,
            'store_accepted_at' => now(),
        ]);
    }

    public function test_job_targets_the_order_customer(): void
    {
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldReceive('sendCustomerAccepted')->once()->with('customer-token', 'ORDER-20', 30);

        (new SendCustomerOrderAcceptedPush(30))->handle($push);
    }

    public function test_job_skips_an_order_without_a_customer_token(): void
    {
        DB::table('users')->where('id', 10)->update(['device_token_food' => null]);
        $push = Mockery::mock(FirebaseMerchantOrderPush::class);
        $push->shouldNotReceive('sendCustomerAccepted');

        (new SendCustomerOrderAcceptedPush(30))->handle($push);
    }

    public function test_firebase_message_contains_the_acceptance_and_order_reference(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $privateKey);
        $path = tempnam(sys_get_temp_dir(), 'customer-push-');
        file_put_contents($path, json_encode([
            'project_id' => 'customer-push-test',
            'client_email' => 'test@example.com',
            'private_key' => $privateKey,
        ]));
        config(['services.firebase.service_account' => $path]);
        Cache::forget('firebase-messaging-token:customer-push-test');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/customer-push-test/messages/1']),
        ]);

        try {
            (new FirebaseMerchantOrderPush)->sendCustomerAccepted('customer-token', 'ORDER-20', 30);

            Http::assertSent(fn ($request) => str_contains($request->url(), '/messages:send')
                && $request['message']['token'] === 'customer-token'
                && $request['message']['notification']['body'] === 'Your order ORDER-20 has been accepted by the merchant.'
                && $request['message']['data'] === [
                    'type' => 'order_accepted',
                    'order_id' => '30',
                    'order_no' => 'ORDER-20',
                ]);
        } finally {
            unlink($path);
            Cache::forget('firebase-messaging-token:customer-push-test');
        }
    }
}
