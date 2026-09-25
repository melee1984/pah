<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_rewards')) {
            return;
        }

        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('order_id')->unique();
            $table->unsignedInteger('points');
            $table->decimal('order_amount', 12, 2);
            $table->decimal('php_per_point', 12, 2);
            $table->string('status', 20)->default('earned')->index();
            $table->timestamp('earned_at')->index();
            $table->timestamp('reversed_at')->nullable();
            $table->string('reversal_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_rewards');
    }
};
