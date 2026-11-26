<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quickpay_checkouts', function (Blueprint $table) {
            $table->unsignedBigInteger('guest_dining_table_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('quickpay_checkouts', function (Blueprint $table) {
            $table->dropIndex(['guest_dining_table_id']);
            $table->dropColumn('guest_dining_table_id');
        });
    }
};
