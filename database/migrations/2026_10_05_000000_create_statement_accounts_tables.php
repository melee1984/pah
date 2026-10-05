<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statement_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('partner_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('order_count')->default(0);
            $table->dateTime('period_start')->nullable();
            $table->dateTime('period_end')->nullable();
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('convenience_fee_amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('delivery_fee_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('merchant_net_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('statement_account_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('statement_account_id')->index();
            $table->unsignedBigInteger('order_id')->unique();
            $table->string('order_number', 100)->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->string('fulfillment_type', 20)->nullable();
            $table->string('order_status', 100)->nullable();
            $table->unsignedInteger('quantity')->default(0);
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('convenience_fee_amount', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('delivery_fee_amount', 14, 2)->default(0);
            $table->decimal('discount_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('commission_amount', 14, 2)->default(0);
            $table->decimal('merchant_net_amount', 14, 2)->default(0);
            $table->timestamps();

            $table->foreign('statement_account_id')
                ->references('id')
                ->on('statement_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statement_account_items');
        Schema::dropIfExists('statement_accounts');
    }
};
