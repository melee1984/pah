<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partner_location') && Schema::hasColumn('partner_location', 'city')) {
            Schema::table('partner_location', function (Blueprint $table) {
                $table->string('city', 100)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('partner_location') || ! Schema::hasColumn('partner_location', 'city')) {
            return;
        }

        $hasLongCity = DB::table('partner_location')
            ->whereRaw('CHAR_LENGTH(city) > 25')
            ->exists();

        if ($hasLongCity) {
            throw new RuntimeException('Cannot restore partner_location.city to 25 characters while longer city names exist.');
        }

        Schema::table('partner_location', function (Blueprint $table) {
            $table->string('city', 25)->nullable()->change();
        });
    }
};
