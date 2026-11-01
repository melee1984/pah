<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user_address') && Schema::hasColumn('user_address', 'city_id')) {
            Schema::table('user_address', function (Blueprint $table) {
                $table->dropColumn('city_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_address') && ! Schema::hasColumn('user_address', 'city_id')) {
            Schema::table('user_address', function (Blueprint $table) {
                $table->unsignedBigInteger('city_id')->nullable()->after('city_name');
            });

            if (Schema::hasTable('cities')) {
                DB::table('user_address')
                    ->whereNotNull('city_name')
                    ->update([
                        'city_id' => DB::raw('(SELECT id FROM cities WHERE cities.name = user_address.city_name LIMIT 1)'),
                    ]);
            }
        }
    }
};
