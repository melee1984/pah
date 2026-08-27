<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agents')) {
            Schema::create('agents', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('mobile', 30)->nullable();
                $table->string('password');
                $table->decimal('commission_percentage', 5, 2)->default(30);
                $table->boolean('active')->default(true)->index();
                $table->rememberToken();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('partners') && ! Schema::hasColumn('partners', 'agent_id')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->unsignedBigInteger('agent_id')->nullable()->index();
            });
        }

        if (! Schema::hasTable('agent_commissions')) {
            Schema::create('agent_commissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('order_id')->unique();
                $table->unsignedBigInteger('restaurant_id')->index();
                $table->unsignedBigInteger('agent_id')->index();
                $table->decimal('order_amount', 12, 2);
                $table->decimal('commission_percentage', 5, 2);
                $table->decimal('commission_amount', 12, 2);
                $table->string('status', 20)->default('pending')->index();
                $table->timestamp('qualified_at')->index();
                $table->timestamp('reversed_at')->nullable();
                $table->string('reversal_reason')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('agent_commissions');

        if (Schema::hasTable('partners') && Schema::hasColumn('partners', 'agent_id')) {
            Schema::table('partners', function (Blueprint $table) {
                $table->dropColumn('agent_id');
            });
        }

        Schema::dropIfExists('agents');
    }
};
