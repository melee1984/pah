<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agent_statement_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->unsignedBigInteger('agent_id')->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->string('status', 20)->default('draft')->index();
            $table->unsignedInteger('transaction_count')->default(0);
            $table->dateTime('period_start')->nullable();
            $table->dateTime('period_end')->nullable();
            $table->decimal('pahatud_commission_amount', 14, 2)->default(0);
            $table->decimal('agent_commission_amount', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('agent_statement_account_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_statement_account_id')->index();
            $table->unsignedBigInteger('agent_commission_id')->unique();
            $table->unsignedBigInteger('order_id')->nullable()->index();
            $table->unsignedBigInteger('restaurant_id')->nullable()->index();
            $table->string('restaurant_name')->nullable();
            $table->string('order_number', 100)->nullable();
            $table->dateTime('qualified_at');
            $table->decimal('order_amount', 14, 2)->default(0);
            $table->decimal('pahatud_commission_percentage', 5, 2)->default(0);
            $table->decimal('pahatud_commission_amount', 14, 2)->default(0);
            $table->decimal('agent_commission_percentage', 5, 2)->default(0);
            $table->decimal('agent_commission_amount', 14, 2)->default(0);
            $table->string('commission_status', 20);
            $table->timestamps();

            $table->foreign('agent_statement_account_id')
                ->references('id')
                ->on('agent_statement_accounts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_statement_account_items');
        Schema::dropIfExists('agent_statement_accounts');
    }
};
