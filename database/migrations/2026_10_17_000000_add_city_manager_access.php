<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->index();
            $table->string('managed_city', 120)->nullable()->index();
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->index()->after('mobile');
        });

        Schema::table('rider', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->index()->after('mobile');
        });

        Schema::table('rider_applications', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->index()->after('home_address');
        });

        DB::table(config('permission.table_names.roles', 'roles'))->insertOrIgnore([
            'name' => 'manager',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::table('rider_applications', fn (Blueprint $table) => $table->dropColumn('city'));
        Schema::table('rider', fn (Blueprint $table) => $table->dropColumn('city'));
        Schema::table('agents', fn (Blueprint $table) => $table->dropColumn('city'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['city', 'managed_city']));

        DB::table(config('permission.table_names.roles', 'roles'))
            ->where('name', 'manager')
            ->where('guard_name', 'web')
            ->delete();
    }
};
