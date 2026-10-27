<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order')) {
            return;
        }

        if (! Schema::hasColumn('order', 'booking_status_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->integer('booking_status_id')->nullable()->after('status_id');
            });
        }

        if (! Schema::hasColumn('order', 'order_status_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->integer('order_status_id')->nullable()->after('booking_status_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('order')) {
            return;
        }

        if (Schema::hasColumn('order', 'order_status_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->dropColumn('order_status_id');
            });
        }

        if (Schema::hasColumn('order', 'booking_status_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->dropColumn('booking_status_id');
            });
        }
    }
};
