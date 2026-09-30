<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_push_notifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('category', 30)->index();
            $table->string('audience_type', 40)->index();
            $table->json('target_ids')->nullable();
            $table->string('title', 120);
            $table->text('message');
            $table->string('image_path')->nullable();
            $table->string('deep_link', 2048)->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_push_notification_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')
                ->constrained('admin_push_notifications')
                ->cascadeOnDelete();
            $table->string('recipient_type', 30);
            $table->unsignedBigInteger('recipient_id')->nullable()->index();
            $table->string('recipient_label')->nullable();
            $table->char('token_hash', 64);
            $table->string('status', 20)->default('pending')->index();
            $table->string('provider_message_id')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['notification_id', 'token_hash'], 'push_delivery_notification_token_unique');
            $table->index(['notification_id', 'status'], 'push_delivery_notification_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_push_notification_deliveries');
        Schema::dropIfExists('admin_push_notifications');
    }
};
