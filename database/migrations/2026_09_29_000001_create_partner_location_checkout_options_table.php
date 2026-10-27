<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_location_checkout_options')) {
            Schema::create('partner_location_checkout_options', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_location_id');
                $table->string('type', 20);
                $table->boolean('active')->default(true);
                $table->timestamps();

                $table->index('partner_location_id', 'plco_location_idx');
                $table->unique(['partner_location_id', 'type'], 'plco_location_type_unique');
            });
        }

        // A failed first run may have created the table before MySQL rejected
        // Laravel's generated index name for exceeding its 64-character limit.
        if (! Schema::hasIndex('partner_location_checkout_options', 'plco_location_idx')) {
            Schema::table('partner_location_checkout_options', function (Blueprint $table) {
                $table->index('partner_location_id', 'plco_location_idx');
            });
        }

        if (! Schema::hasIndex('partner_location_checkout_options', 'plco_location_type_unique')) {
            Schema::table('partner_location_checkout_options', function (Blueprint $table) {
                $table->unique(['partner_location_id', 'type'], 'plco_location_type_unique');
            });
        }

        if (Schema::hasTable('partner_location')) {
            DB::table('partner_location')
                ->select('id')
                ->orderBy('id')
                ->chunkById(200, function ($locations) {
                    $now = now();
                    $rows = [];

                    foreach ($locations as $location) {
                        foreach (['delivery', 'pickup', 'dine_in'] as $type) {
                            $rows[] = [
                                'partner_location_id' => $location->id,
                                'type' => $type,
                                'active' => true,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    DB::table('partner_location_checkout_options')->insertOrIgnore($rows);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_location_checkout_options');
    }
};
