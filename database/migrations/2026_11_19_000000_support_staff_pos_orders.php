<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart') && ! Schema::hasColumn('cart', 'notes')) {
            Schema::table('cart', fn (Blueprint $table) => $table->text('notes')->nullable());
        }
        if (Schema::hasTable('cart') && ! Schema::hasColumn('cart', 'pos_request_hash')) {
            Schema::table('cart', fn (Blueprint $table) => $table->string('pos_request_hash', 64)->nullable());
        }
        Schema::table('dining_bills', fn (Blueprint $table) => $table->unsignedBigInteger('dining_table_id')->nullable()->change());
    }

    public function down(): void
    {
        // Keep notes and table-less POS bills: reverting would discard existing data.
    }
};
