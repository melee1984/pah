<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quickpay_checkouts', function (Blueprint $table) {
            $table->unsignedBigInteger('rider_delivery_id')->nullable();
            $table->uuid('mobile_attempt_key')->nullable();
            $table->index(['rider_delivery_id', 'id']);
            $table->unique(['rider_delivery_id', 'mobile_attempt_key']);
        });
    }

    public function down(): void
    {
        Schema::table('quickpay_checkouts', function (Blueprint $table) {
            $table->dropUnique(['rider_delivery_id', 'mobile_attempt_key']);
            $table->dropIndex(['rider_delivery_id', 'id']);
            $table->dropColumn(['rider_delivery_id', 'mobile_attempt_key']);
        });
    }
};
