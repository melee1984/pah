<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('qr_ordering_codes')) {
            Schema::create('qr_ordering_codes', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('partner_id')->index();
                $table->unsignedBigInteger('partner_location_id')->index();
                $table->unsignedBigInteger('dining_table_id')->nullable()->index();
                $table->string('label', 100)->nullable();
                $table->boolean('active')->default(true)->index();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['partner_location_id', 'dining_table_id'],
                    'qr_ordering_codes_location_table_unique'
                );
            });
        }

        if (Schema::hasTable('partner_location_tables') && Schema::hasTable('partner_location')) {
            DB::table('partner_location_tables')
                ->join(
                    'partner_location',
                    'partner_location.id',
                    '=',
                    'partner_location_tables.partner_location_id'
                )
                ->select([
                    'partner_location_tables.id',
                    'partner_location_tables.partner_location_id',
                    'partner_location_tables.name',
                    'partner_location.partner_id',
                ])
                ->orderBy('partner_location_tables.id')
                ->chunk(200, function ($tables) {
                    foreach ($tables as $diningTable) {
                        DB::table('qr_ordering_codes')->insertOrIgnore([
                            'uuid' => (string) Str::uuid(),
                            'partner_id' => $diningTable->partner_id,
                            'partner_location_id' => $diningTable->partner_location_id,
                            'dining_table_id' => $diningTable->id,
                            'label' => $diningTable->name,
                            'active' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                });
        }

        // Guest orders intentionally have no user account.
        if (Schema::hasTable('order') && Schema::hasColumn('order', 'user_id')) {
            Schema::table('order', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_ordering_codes');

        // Do not make order.user_id required again: existing guest orders may have null user IDs.
    }
};
