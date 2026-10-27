<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_api_wallet_top_ups')) {
            Schema::create('rider_api_wallet_top_ups', function (Blueprint $table) {
                $table->id();
                $table->uuid('reference')->unique();
                $table->unsignedBigInteger('rider_id')->index();
                $table->unsignedBigInteger('amount_centavos');
                $table->string('payment_method', 50);
                $table->string('payment_reference', 150);
                $table->string('proof_path');
                $table->string('proof_original_name');
                $table->string('proof_mime_type', 100)->nullable();
                $table->string('status', 20)->default('pending')->index();
                $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_notes')->nullable();
                $table->timestamps();

                $table->unique(['rider_id', 'payment_method', 'payment_reference'], 'rider_wallet_top_up_payment_unique');
                $table->index(['rider_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_api_wallet_top_ups');
    }
};
