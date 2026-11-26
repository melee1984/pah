<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quickpay_checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dining_bill_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('partner_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->unsignedBigInteger('actor_id');
            $table->unsignedBigInteger('amount_cents');
            $table->uuid('request_key')->unique();
            $table->boolean('livemode');
            $table->string('status', 12)->default('pending')->index();
            $table->json('request_body');
            $table->string('provider_id')->nullable();
            $table->text('url')->nullable();
            $table->text('qr_payload')->nullable();
            $table->mediumText('qr_image')->nullable();
            $table->string('expires_at')->nullable();
            $table->timestamps();
            $table->index(['dining_bill_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quickpay_checkouts');
    }
};
