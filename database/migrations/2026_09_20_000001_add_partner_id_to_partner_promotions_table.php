<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partner_promotions', function (Blueprint $table) {
            $table->unsignedInteger('partner_id')->nullable()->after('id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('partner_promotions', function (Blueprint $table) {
            $table->dropColumn('partner_id');
        });
    }
};
