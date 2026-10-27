<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'commission_percentage')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->decimal('commission_percentage', 5, 2)->default(15)->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'commission_percentage')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->decimal('commission_percentage', 5, 2)->default(30)->change();
            });
        }
    }
};
