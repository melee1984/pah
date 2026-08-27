<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('order') && ! Schema::hasColumn('order', 'accepted_by_rider_at')) {
            Schema::table('order', function (Blueprint $table) {
                $table->dateTime('accepted_by_rider_at')->nullable()->after('accepted_by_rider_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('order') && Schema::hasColumn('order', 'accepted_by_rider_at')) {
            Schema::table('order', function (Blueprint $table) {
                $table->dropColumn('accepted_by_rider_at');
            });
        }
    }
};
