<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number', 40)->unique();
            // Legacy users/orders are managed outside migrations; do not alter them.
            $table->unsignedBigInteger('user_id')->index();
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_mobile')->nullable();
            $table->string('category', 50)->index();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('subject', 180);
            $table->unsignedBigInteger('assigned_to')->nullable()->index();
            $table->string('status', 30)->default('Open')->index();
            $table->string('priority', 20)->default('Normal')->index();
            $table->timestamps();
        });
        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('author');
            $table->string('kind', 20); // customer, staff, internal, event
            $table->text('body');
            $table->timestamps();
        });
        Schema::create('support_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('support_messages')->cascadeOnDelete();
            $table->string('name');
            $table->string('mime', 40);
            // Base64 keeps screenshots portable between MySQL and SQLite.
            $table->longText('content');
        });
        Schema::create('support_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('audience', 10);
            $table->string('summary');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'audience', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_alerts');
        Schema::dropIfExists('support_attachments');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
    }
};
