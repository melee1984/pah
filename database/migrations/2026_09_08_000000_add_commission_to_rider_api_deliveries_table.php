<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_api_deliveries', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(20)->after('earnings_centavos');
            $table->unsignedBigInteger('commission_centavos')->default(0)->after('commission_percentage');
        });

        DB::table('rider_api_deliveries')->update([
            'commission_percentage' => config('rider.pahatud_commission_percentage', 20),
            'commission_centavos' => DB::raw(
                'ROUND(earnings_centavos * '.((float) config('rider.pahatud_commission_percentage', 20)).' / 100)'
            ),
        ]);
    }

    public function down(): void
    {
        Schema::table('rider_api_deliveries', function (Blueprint $table) {
            $table->dropColumn(['commission_percentage', 'commission_centavos']);
        });
    }
};
