<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_commissions')) {
            return;
        }

        $columns = [
            'subtotal_amount' => fn (Blueprint $table) => $table->decimal('subtotal_amount', 12, 2)->nullable(),
            'delivery_fee_amount' => fn (Blueprint $table) => $table->decimal('delivery_fee_amount', 12, 2)->nullable(),
            'discount_amount' => fn (Blueprint $table) => $table->decimal('discount_amount', 12, 2)->nullable(),
            'total_amount' => fn (Blueprint $table) => $table->decimal('total_amount', 12, 2)->nullable(),
            'pahatud_commission_percentage' => fn (Blueprint $table) => $table->decimal('pahatud_commission_percentage', 5, 2)->nullable(),
            'pahatud_commission_amount' => fn (Blueprint $table) => $table->decimal('pahatud_commission_amount', 12, 2)->nullable(),
        ];

        foreach ($columns as $column => $definition) {
            if (! Schema::hasColumn('agent_commissions', $column)) {
                Schema::table('agent_commissions', $definition);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_commissions')) {
            return;
        }

        foreach ([
            'subtotal_amount',
            'delivery_fee_amount',
            'discount_amount',
            'total_amount',
            'pahatud_commission_percentage',
            'pahatud_commission_amount',
        ] as $column) {
            if (Schema::hasColumn('agent_commissions', $column)) {
                Schema::table('agent_commissions', fn (Blueprint $table) => $table->dropColumn($column));
            }
        }
    }
};
