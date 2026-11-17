<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cart')) {
            return;
        }

        $addOrderSource = ! Schema::hasColumn('cart', 'order_source');
        $addQrOrderingCode = ! Schema::hasColumn('cart', 'qr_ordering_code_id');

        if ($addOrderSource || $addQrOrderingCode) {
            Schema::table('cart', function (Blueprint $table) use ($addOrderSource, $addQrOrderingCode) {
                if ($addOrderSource) {
                    $table->string('order_source', 40)->nullable()->index();
                }

                if ($addQrOrderingCode) {
                    $table->unsignedBigInteger('qr_ordering_code_id')->nullable()->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('cart')) {
            return;
        }

        if (Schema::hasColumn('cart', 'qr_ordering_code_id')) {
            Schema::table('cart', fn (Blueprint $table) => $table->dropColumn('qr_ordering_code_id'));
        }

        if (Schema::hasColumn('cart', 'order_source')) {
            Schema::table('cart', fn (Blueprint $table) => $table->dropColumn('order_source'));
        }
    }
};
