<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cart_user_address') && ! Schema::hasColumn('cart_user_address', 'title')) {
            Schema::table('cart_user_address', function (Blueprint $table) {
                $table->string('title')->nullable()->after('user_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_user_address') && Schema::hasColumn('cart_user_address', 'title')) {
            Schema::table('cart_user_address', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
    }
};
