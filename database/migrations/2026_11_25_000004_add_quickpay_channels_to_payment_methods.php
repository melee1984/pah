<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_method')) {
            return;
        }

        Schema::table('payment_method', function (Blueprint $table) {
            $table->string('quickpay_channel', 20)->nullable();
        });

        foreach (['maya' => 'Maya', 'gcash' => 'GCash', 'qrph' => 'QR Ph', 'banks' => 'Transfer to bank'] as $channel => $title) {
            DB::table('payment_method')->insertOrIgnore([
                'title' => $title.' (QuickPay)',
                'quickpay_channel' => $channel,
                'active' => true,
                'available_in_qr' => true,
                'available_in_application' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('payment_method', 'quickpay_channel')) {
            DB::table('payment_method')->whereNotNull('quickpay_channel')->update([
                'active' => false, 'available_in_qr' => false, 'available_in_application' => false,
            ]);
            Schema::table('payment_method', fn (Blueprint $table) => $table->dropColumn('quickpay_channel'));
        }
    }
};
