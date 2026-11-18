<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dining_payments', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_tendered_cents')->nullable()->after('amount_cents');
            $table->unsignedBigInteger('change_cents')->nullable()->after('cash_tendered_cents');
        });
    }

    public function down(): void
    {
        Schema::table('dining_payments', function (Blueprint $table) {
            $table->dropColumn(['cash_tendered_cents', 'change_cents']);
        });
    }
};
