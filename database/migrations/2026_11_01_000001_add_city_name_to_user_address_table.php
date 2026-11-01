<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_address') || Schema::hasColumn('user_address', 'city_name')) {
            return;
        }

        Schema::table('user_address', function (Blueprint $table) {
            $table->string('city_name', 120)->nullable()->after('city_id');
        });

        if (! Schema::hasTable('cities')) {
            return;
        }

        DB::table('user_address')
            ->whereNotNull('city_id')
            ->orderBy('id')
            ->chunkById(200, function ($addresses) {
                $cities = DB::table('cities')
                    ->whereIn('id', $addresses->pluck('city_id')->filter()->unique())
                    ->pluck('name', 'id');

                foreach ($addresses as $address) {
                    $cityName = $cities->get($address->city_id);

                    if (filled($cityName)) {
                        DB::table('user_address')
                            ->where('id', $address->id)
                            ->update(['city_name' => $cityName]);
                    }
                }
            });
    }

    public function down(): void
    {
        if (Schema::hasTable('user_address') && Schema::hasColumn('user_address', 'city_name')) {
            Schema::table('user_address', function (Blueprint $table) {
                $table->dropColumn('city_name');
            });
        }
    }
};
