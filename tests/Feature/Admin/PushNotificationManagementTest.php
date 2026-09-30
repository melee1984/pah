<?php

namespace Tests\Feature\Admin;

use App\AdminPushNotification;
use App\Jobs\SendAdminPushNotification;
use App\Services\AdminPushRecipientResolver;
use App\Services\FirebasePush;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PushNotificationManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.key' => 'base64:'.base64_encode(str_repeat('p', 32)),
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $migration = require database_path('migrations/2026_09_29_000000_create_admin_push_notifications_tables.php');
        $migration->up();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname')->nullable();
            $table->string('lastname')->nullable();
            $table->string('email')->nullable();
            $table->string('mobile')->nullable();
            $table->string('device_token_food')->nullable();
        });
        Schema::create('rider', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('mobile')->nullable();
        });
        Schema::create('rider_api_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id');
            $table->text('push_token')->nullable();
            $table->timestamp('revoked_at')->nullable();
        });
        Schema::create('rider_api_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->unique();
            $table->boolean('marketing')->default(false);
        });
        Schema::create('rider_api_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id');
            $table->string('type', 50);
            $table->string('title');
            $table->text('body');
            $table->string('deep_link')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('restaurant_name')->nullable();
        });
        Schema::create('partner_location', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->nullable();
            $table->string('address_1')->nullable();
            $table->string('device_token')->nullable();
        });
    }

    public function test_selected_users_are_filtered_and_duplicate_tokens_are_removed(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'firstname' => 'Ana', 'lastname' => 'Cruz', 'email' => 'ana@example.test', 'device_token_food' => 'shared-token'],
            ['id' => 2, 'firstname' => 'Ben', 'lastname' => 'Santos', 'email' => 'ben@example.test', 'device_token_food' => 'shared-token'],
            ['id' => 3, 'firstname' => 'Cara', 'lastname' => 'Reyes', 'email' => 'cara@example.test', 'device_token_food' => 'cara-token'],
        ]);

        $resolver = app(AdminPushRecipientResolver::class);

        $this->assertSame(0, $resolver->count(AdminPushNotification::AUDIENCE_SELECTED_USERS));
        $this->assertSame(1, $resolver->count(AdminPushNotification::AUDIENCE_SELECTED_USERS, [1, 2]));
        $this->assertSame(2, $resolver->count(AdminPushNotification::AUDIENCE_ALL_USERS));
    }

    public function test_rider_promotions_respect_the_marketing_preference(): void
    {
        DB::table('rider')->insert([
            ['id' => 10, 'name' => 'Opted in rider'],
            ['id' => 11, 'name' => 'Default rider'],
        ]);
        DB::table('rider_api_devices')->insert([
            ['rider_id' => 10, 'push_token' => Crypt::encryptString('rider-token-10')],
            ['rider_id' => 11, 'push_token' => Crypt::encryptString('rider-token-11')],
        ]);
        DB::table('rider_api_notification_preferences')->insert([
            'rider_id' => 10,
            'marketing' => true,
        ]);

        $resolver = app(AdminPushRecipientResolver::class);

        $this->assertSame(1, $resolver->count(
            AdminPushNotification::AUDIENCE_ALL_RIDERS,
            [],
            AdminPushNotification::CATEGORY_PROMOTION,
        ));
        $this->assertSame(2, $resolver->count(
            AdminPushNotification::AUDIENCE_ALL_RIDERS,
            [],
            AdminPushNotification::CATEGORY_ALERT,
        ));
    }

    public function test_send_job_records_successes_and_failures_without_storing_raw_tokens(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'firstname' => 'Ana', 'lastname' => 'Cruz', 'device_token_food' => 'good-token'],
            ['id' => 2, 'firstname' => 'Ben', 'lastname' => 'Santos', 'device_token_food' => 'bad-token'],
        ]);
        $notification = AdminPushNotification::create([
            'reference' => (string) Str::uuid(),
            'category' => AdminPushNotification::CATEGORY_ALERT,
            'audience_type' => AdminPushNotification::AUDIENCE_ALL_USERS,
            'title' => 'Service alert',
            'message' => 'Delivery times may be longer than usual.',
            'status' => AdminPushNotification::STATUS_QUEUED,
        ]);

        $push = Mockery::mock(FirebasePush::class);
        $push->shouldReceive('send')->once()->withArgs(fn ($token) => $token === 'good-token')->andReturn('messages/good');
        $push->shouldReceive('send')->once()->withArgs(fn ($token) => $token === 'bad-token')->andThrow(new RuntimeException('Invalid registration token.'));

        (new SendAdminPushNotification($notification->id))->handle(app(AdminPushRecipientResolver::class), $push);

        $notification->refresh();
        $this->assertSame(AdminPushNotification::STATUS_PARTIAL, $notification->status);
        $this->assertSame(2, $notification->recipient_count);
        $this->assertSame(1, $notification->success_count);
        $this->assertSame(1, $notification->failed_count);
        $this->assertDatabaseHas('admin_push_notification_deliveries', ['status' => 'sent']);
        $this->assertDatabaseHas('admin_push_notification_deliveries', ['status' => 'failed']);
        $this->assertDatabaseMissing('admin_push_notification_deliveries', ['token_hash' => 'good-token']);
        $this->assertDatabaseMissing('admin_push_notification_deliveries', ['token_hash' => 'bad-token']);
    }

    public function test_firebase_payload_contains_category_link_and_optional_image(): void
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048]);
        openssl_pkey_export($key, $privateKey);
        $path = tempnam(sys_get_temp_dir(), 'admin-push-');
        file_put_contents($path, json_encode([
            'project_id' => 'admin-push-test',
            'client_email' => 'test@example.com',
            'private_key' => $privateKey,
        ]));
        config(['services.firebase.service_account' => $path]);
        Cache::forget('firebase-messaging-token:admin-push-test');
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'test-access-token']),
            'fcm.googleapis.com/*' => Http::response(['name' => 'projects/admin-push-test/messages/1']),
        ]);

        try {
            $messageId = (new FirebasePush)->send(
                'customer-token',
                'Weekend offer',
                'Save on your next delivery.',
                ['category' => 'promotion', 'deep_link' => 'pahatud://promotions/1'],
                'https://pahatud.test/uploads/push-notifications/offer.jpg',
            );

            $this->assertSame('projects/admin-push-test/messages/1', $messageId);
            Http::assertSent(fn ($request) => str_contains($request->url(), '/messages:send')
                && $request['message']['token'] === 'customer-token'
                && $request['message']['data']['category'] === 'promotion'
                && $request['message']['data']['deep_link'] === 'pahatud://promotions/1'
                && $request['message']['notification']['image'] === 'https://pahatud.test/uploads/push-notifications/offer.jpg'
                && $request['message']['android']['notification']['image'] === 'https://pahatud.test/uploads/push-notifications/offer.jpg'
                && $request['message']['apns']['fcm_options']['image'] === 'https://pahatud.test/uploads/push-notifications/offer.jpg');
        } finally {
            unlink($path);
            Cache::forget('firebase-messaging-token:admin-push-test');
        }
    }
}
