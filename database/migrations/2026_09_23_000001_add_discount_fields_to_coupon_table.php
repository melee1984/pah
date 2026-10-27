<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('coupon')) {
            return;
        }

        Schema::table('coupon', function (Blueprint $table) {
            $table->decimal('discount_percentage', 5, 2)->nullable()->after('discount_value');
            $table->dateTime('valid_from')->nullable()->after('coupon');
            $table->dateTime('valid_until')->nullable()->after('valid_from');
            $table->index(['active', 'partner_id'], 'coupon_active_partner_index');
            $table->index(['valid_from', 'valid_until'], 'coupon_validity_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('coupon')) {
            return;
        }

        Schema::table('coupon', function (Blueprint $table) {
            $table->dropIndex('coupon_active_partner_index');
            $table->dropIndex('coupon_validity_index');
            $table->dropColumn(['discount_percentage', 'valid_from', 'valid_until']);
        });
    }
};
