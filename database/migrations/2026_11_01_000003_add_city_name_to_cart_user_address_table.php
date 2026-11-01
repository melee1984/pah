<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cart_user_address') || Schema::hasColumn('cart_user_address', 'city_name')) {
            return;
        }

        Schema::table('cart_user_address', function (Blueprint $table) {
            $table->string('city_name', 120)->nullable()->after('city_id');
        });

        if (Schema::hasTable('cities') && Schema::hasColumn('cart_user_address', 'city_id')) {
            DB::table('cart_user_address')
                ->whereNotNull('city_id')
                ->update([
                    'city_name' => DB::raw('(SELECT name FROM cities WHERE cities.id = cart_user_address.city_id LIMIT 1)'),
                ]);
        }

        if (Schema::hasTable('cart') && Schema::hasTable('user_address')) {
            DB::table('cart_user_address')
                ->whereNull('city_name')
                ->update([
                    'city_name' => DB::raw('(SELECT user_address.city_name FROM cart JOIN user_address ON user_address.id = cart.address_id WHERE cart.id = cart_user_address.cart_id LIMIT 1)'),
                ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart_user_address') && Schema::hasColumn('cart_user_address', 'city_name')) {
            Schema::table('cart_user_address', function (Blueprint $table) {
                $table->dropColumn('city_name');
            });
        }
    }
};
