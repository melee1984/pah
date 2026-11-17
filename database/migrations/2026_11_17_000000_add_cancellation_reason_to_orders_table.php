<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order') || Schema::hasColumn('order', 'cancellation_reason')) {
            return;
        }

        Schema::table('order', function (Blueprint $table) {
            $table->text('cancellation_reason')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('order') || ! Schema::hasColumn('order', 'cancellation_reason')) {
            return;
        }

        Schema::table('order', fn (Blueprint $table) => $table->dropColumn('cancellation_reason'));
    }
};
