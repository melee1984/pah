<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_location_tables') || ! Schema::hasTable('partner_location')) {
            return;
        }

        $hasLocationForeignKey = collect(Schema::getForeignKeys('partner_location_tables'))
            ->contains(fn (array $foreignKey) => $foreignKey['columns'] === ['partner_location_id']);

        if (! $hasLocationForeignKey) {
            Schema::table('partner_location_tables', function (Blueprint $table) {
                $table->foreign('partner_location_id', 'plt_location_fk')
                    ->references('id')
                    ->on('partner_location')
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('partner_location_tables')) {
            return;
        }

        $hasLocationForeignKey = collect(Schema::getForeignKeys('partner_location_tables'))
            ->contains(fn (array $foreignKey) => ($foreignKey['name'] ?? null) === 'plt_location_fk');

        if ($hasLocationForeignKey) {
            Schema::table('partner_location_tables', function (Blueprint $table) {
                $table->dropForeign('plt_location_fk');
            });
        }
    }
};
