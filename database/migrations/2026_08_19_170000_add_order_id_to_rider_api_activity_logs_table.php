<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider_api_activity_logs') && ! Schema::hasColumn('rider_api_activity_logs', 'order_id')) {
            Schema::table('rider_api_activity_logs', function (Blueprint $table) {
                $table->unsignedBigInteger('order_id')->nullable()->after('rider_id')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('rider_api_activity_logs') && Schema::hasColumn('rider_api_activity_logs', 'order_id')) {
            Schema::table('rider_api_activity_logs', function (Blueprint $table) {
                $table->dropColumn('order_id');
            });
        }
    }
};
