<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('partner_location') && ! Schema::hasColumn('partner_location', 'device_token')) {
            Schema::table('partner_location', function (Blueprint $table) {
                $table->string('device_token')->nullable()->after('mobile');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('partner_location') && Schema::hasColumn('partner_location', 'device_token')) {
            Schema::table('partner_location', function (Blueprint $table) {
                $table->dropColumn('device_token');
            });
        }
    }
};
