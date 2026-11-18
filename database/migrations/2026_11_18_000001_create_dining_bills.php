<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_bills', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('partner_id')->index();
            $table->unsignedBigInteger('partner_location_id')->index();
            $table->unsignedBigInteger('dining_table_id')->index();
            $table->string('status', 20)->default('open')->index();
            $table->json('totals')->nullable();
            $table->json('receipt')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });
        Schema::create('dining_bill_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dining_bill_id')->constrained('dining_bills')->restrictOnDelete();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedInteger('round');
            $table->json('amounts');
            $table->json('items');
            $table->timestamps();
            $table->unique(['dining_bill_id', 'round']);
        });
        Schema::create('dining_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dining_bill_id')->unique()->constrained('dining_bills')->restrictOnDelete();
            $table->uuid('idempotency_key')->unique();
            $table->unsignedBigInteger('recorded_by_user_id');
            $table->unsignedBigInteger('payment_method_id');
            $table->string('method_name', 100);
            $table->unsignedBigInteger('amount_cents');
            $table->string('reference', 150)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dining_payments');
        Schema::dropIfExists('dining_bill_orders');
        Schema::dropIfExists('dining_bills');
    }
};
