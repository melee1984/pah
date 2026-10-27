<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120)->unique();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        $cityNames = collect();

        foreach (['partners', 'agents'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'city')) {
                $cityNames = $cityNames->merge(
                    DB::table($table)->whereNotNull('city')->where('city', '!=', '')->pluck('city')
                );
            }
        }

        $seen = [];
        $cities = $cityNames
            ->map(fn ($city) => trim((string) $city))
            ->filter()
            ->filter(function ($city) use (&$seen) {
                $key = mb_strtolower($city);

                return ! isset($seen[$key]) && ($seen[$key] = true);
            })
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->map(fn ($city) => [
                'name' => $city,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->values()
            ->all();

        if ($cities !== []) {
            DB::table('cities')->insert($cities);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
