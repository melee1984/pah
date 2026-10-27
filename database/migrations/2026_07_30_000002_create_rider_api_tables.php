<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider')) {
            Schema::create('rider', function (Blueprint $table) {
                $table->id();
                $table->string('name', 250)->nullable();
                $table->dateTime('date_join')->nullable();
                $table->integer('license_no')->nullable();
                $table->string('mobile', 15)->nullable();
                $table->boolean('active')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamps();
            });
        }

        Schema::create('rider_api_devices', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->unsignedBigInteger('personal_access_token_id')->nullable()->index();
            $table->string('device_key');
            $table->text('push_token')->nullable();
            $table->string('platform', 30)->nullable();
            $table->string('device_model')->nullable();
            $table->string('app_version', 30)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['rider_id', 'device_key']);
        });

        Schema::create('rider_api_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->unique();
            $table->string('state', 30)->default('offline')->index();
            $table->json('schedule')->nullable();
            $table->json('zone_preferences')->nullable();
            $table->timestamp('heartbeat_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_zones', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('name');
            $table->json('boundary')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('rider_api_locations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->index();
            $table->uuid('delivery_reference')->nullable()->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_meters', 8, 2)->nullable();
            $table->decimal('heading', 6, 2)->nullable();
            $table->decimal('speed_mps', 8, 2)->nullable();
            $table->timestamp('recorded_at')->index();
            $table->timestamps();
        });

        Schema::create('rider_api_otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('purpose', 30);
            $table->string('channel', 20);
            $table->string('destination');
            $table->char('code_hash', 64);
            $table->char('verification_token_hash', 64)->nullable()->unique();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_deliveries', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_order_id')->nullable()->index();
            $table->unsignedBigInteger('legacy_booking_id')->nullable()->index();
            $table->string('current_state', 50)->default('offered')->index();
            $table->string('merchant_name')->nullable();
            $table->string('pickup_area')->nullable();
            $table->text('pickup_address')->nullable();
            $table->decimal('pickup_latitude', 10, 7)->nullable();
            $table->decimal('pickup_longitude', 10, 7)->nullable();
            $table->string('dropoff_area')->nullable();
            $table->text('dropoff_address')->nullable();
            $table->decimal('dropoff_latitude', 10, 7)->nullable();
            $table->decimal('dropoff_longitude', 10, 7)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->unsignedInteger('distance_meters')->nullable();
            $table->unsignedInteger('eta_seconds')->nullable();
            $table->unsignedBigInteger('earnings_centavos')->default(0);
            $table->unsignedBigInteger('cod_centavos')->default(0);
            $table->unsignedInteger('order_count')->default(1);
            $table->boolean('is_batched')->default(false);
            $table->char('pickup_code_hash', 64)->nullable();
            $table->char('customer_code_hash', 64)->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_offers', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->unsignedBigInteger('delivery_id');
            $table->string('status', 20)->default('pending')->index();
            $table->string('decline_reason')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->unique(['rider_id', 'delivery_id']);
        });

        Schema::create('rider_api_delivery_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_id')->index();
            $table->uuid('event_id')->unique();
            $table->string('type', 50)->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();
        });

        Schema::create('rider_api_proof_uploads', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('delivery_id')->index();
            $table->char('upload_token_hash', 64)->unique();
            $table->string('method', 20);
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_delivery_proofs', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('delivery_id')->index();
            $table->string('method', 20);
            $table->string('path')->nullable();
            $table->json('metadata')->nullable();
            $table->string('processing_status', 20)->default('complete');
            $table->timestamps();
        });

        Schema::create('rider_api_delivery_issues', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('delivery_id')->index();
            $table->string('type', 50);
            $table->text('description');
            $table->string('status', 20)->default('open')->index();
            $table->timestamps();
        });

        Schema::create('rider_api_delivery_calls', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('delivery_id')->index();
            $table->string('party', 20);
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('rider_api_share_links', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('delivery_id')->index();
            $table->char('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_wallets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->unique();
            $table->bigInteger('available_centavos')->default(0);
            $table->bigInteger('pending_centavos')->default(0);
            $table->bigInteger('cash_collected_centavos')->default(0);
            $table->bigInteger('amount_owed_centavos')->default(0);
            $table->unsignedBigInteger('daily_cod_limit_centavos')->default(0);
            $table->timestamps();
        });

        Schema::create('rider_api_wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->string('type', 40)->index();
            $table->bigInteger('amount_centavos');
            $table->bigInteger('balance_after_centavos');
            $table->string('description');
            $table->string('related_type', 40)->nullable();
            $table->uuid('related_reference')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();
        });

        Schema::create('rider_api_cod_remittances', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->unsignedBigInteger('amount_centavos');
            $table->string('proof_path');
            $table->string('status', 20)->default('pending')->index();
            $table->text('review_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->string('method', 50);
            $table->string('account_name');
            $table->text('account_number');
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_withdrawals', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->unsignedBigInteger('payout_account_id');
            $table->unsignedBigInteger('amount_centavos');
            $table->string('status', 20)->default('pending')->index();
            $table->timestamps();
        });

        Schema::create('rider_api_wallet_disputes', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->uuid('transaction_reference')->nullable();
            $table->string('type', 50);
            $table->text('description');
            $table->string('status', 20)->default('open')->index();
            $table->timestamps();
        });

        Schema::create('rider_api_conversations', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->string('type', 20);
            $table->uuid('delivery_reference')->nullable()->index();
            $table->string('subject')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_messages', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->uuid('client_message_id')->unique();
            $table->string('sender_type', 20);
            $table->text('body');
            $table->string('status', 20)->default('sent');
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('conversation_id')->index();
            $table->unsignedBigInteger('message_id')->nullable()->index();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });

        Schema::create('rider_api_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->string('type', 50)->index();
            $table->string('title');
            $table->text('body');
            $table->string('deep_link')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->unique();
            $table->boolean('delivery_offers')->default(true);
            $table->boolean('delivery_updates')->default(true);
            $table->boolean('wallet_updates')->default(true);
            $table->boolean('support_messages')->default(true);
            $table->boolean('marketing')->default(false);
            $table->timestamps();
        });

        Schema::create('rider_api_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rider_id')->unique();
            $table->string('language', 10)->default('en');
            $table->string('navigation_app', 30)->default('system');
            $table->boolean('share_live_location')->default(true);
            $table->boolean('background_location')->default(true);
            $table->timestamps();
        });

        Schema::create('rider_api_feedback', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->uuid('delivery_reference')->nullable();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('rider_api_delete_requests', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('rider_id')->index();
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $tables = [
            'rider_api_delete_requests',
            'rider_api_feedback',
            'rider_api_settings',
            'rider_api_notification_preferences',
            'rider_api_notifications',
            'rider_api_message_attachments',
            'rider_api_messages',
            'rider_api_conversations',
            'rider_api_wallet_disputes',
            'rider_api_withdrawals',
            'rider_api_payout_accounts',
            'rider_api_cod_remittances',
            'rider_api_wallet_transactions',
            'rider_api_wallets',
            'rider_api_share_links',
            'rider_api_delivery_calls',
            'rider_api_delivery_issues',
            'rider_api_delivery_proofs',
            'rider_api_proof_uploads',
            'rider_api_delivery_events',
            'rider_api_offers',
            'rider_api_deliveries',
            'rider_api_otp_challenges',
            'rider_api_locations',
            'rider_api_zones',
            'rider_api_availability',
            'rider_api_devices',
        ];

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
};
