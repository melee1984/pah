<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('rider') && ! Schema::hasColumn('rider', 'approved_at')) {
            Schema::table('rider', function (Blueprint $table) {
                $table->dateTime('approved_at')->nullable()->after('is_active')->index();
            });

            DB::table('rider')
                ->where('active', true)
                ->whereNull('approved_at')
                ->update(['approved_at' => DB::raw('COALESCE(date_join, updated_at, created_at, CURRENT_TIMESTAMP)')]);
        }

        if (
            Schema::hasTable('rider_api_wallet_transactions')
            && ! Schema::hasColumn('rider_api_wallet_transactions', 'performed_by_user_id')
        ) {
            Schema::table('rider_api_wallet_transactions', function (Blueprint $table) {
                $table->unsignedBigInteger('performed_by_user_id')
                    ->nullable()
                    ->after('related_reference')
                    ->index();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('rider_api_wallet_transactions')
            && Schema::hasColumn('rider_api_wallet_transactions', 'performed_by_user_id')
        ) {
            Schema::table('rider_api_wallet_transactions', function (Blueprint $table) {
                $table->dropColumn('performed_by_user_id');
            });
        }

        if (Schema::hasTable('rider') && Schema::hasColumn('rider', 'approved_at')) {
            Schema::table('rider', function (Blueprint $table) {
                $table->dropColumn('approved_at');
            });
        }
    }
};
