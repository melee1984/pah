<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rider_api_wallets', function (Blueprint $table) {
            $table->decimal('credit_amount', 12, 2)->default(0)->after('rider_id');
            $table->unsignedBigInteger('credit_points')->default(0)->after('credit_amount');
        });

        DB::table('rider_api_wallets')->update([
            'credit_amount' => DB::raw('available_centavos / 100'),
        ]);

        Schema::table('rider_api_wallets', function (Blueprint $table) {
            $table->dropColumn([
                'available_centavos',
                'pending_centavos',
                'cash_collected_centavos',
                'amount_owed_centavos',
                'daily_cod_limit_centavos',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rider_api_wallets', function (Blueprint $table) {
            $table->bigInteger('available_centavos')->default(0)->after('rider_id');
            $table->bigInteger('pending_centavos')->default(0)->after('available_centavos');
            $table->bigInteger('cash_collected_centavos')->default(0)->after('pending_centavos');
            $table->bigInteger('amount_owed_centavos')->default(0)->after('cash_collected_centavos');
            $table->unsignedBigInteger('daily_cod_limit_centavos')->default(0)->after('amount_owed_centavos');
        });

        DB::table('rider_api_wallets')->update([
            'available_centavos' => DB::raw('ROUND(credit_amount * 100)'),
        ]);

        Schema::table('rider_api_wallets', function (Blueprint $table) {
            $table->dropColumn(['credit_amount', 'credit_points']);
        });
    }
};
