<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quickpay_checkouts', function (Blueprint $table) {
            $table->unsignedBigInteger('dining_bill_id')->nullable()->change();
            $table->unsignedBigInteger('actor_id')->nullable()->change();
            $table->unsignedBigInteger('cart_id')->nullable();
            $table->json('guest_snapshot')->nullable();
            $table->index(['cart_id', 'id']);
        });
        Schema::table('dining_payments', fn (Blueprint $table) => $table->unsignedBigInteger('recorded_by_user_id')->nullable()->change());
    }

    public function down(): void
    {
        // Existing guest payments have no staff actor. Keep their audit fields nullable.
        Schema::table('quickpay_checkouts', function (Blueprint $table) {
            $table->dropIndex(['cart_id', 'id']);
            $table->dropColumn(['cart_id', 'guest_snapshot']);
        });
    }
};
