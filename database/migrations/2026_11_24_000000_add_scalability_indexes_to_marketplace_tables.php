<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndex('order', ['partner_id', 'submitted_at'], 'order_partner_submitted_idx');
        $this->addIndex('order_process', ['order_id', 'status_id'], 'order_process_order_status_idx');
        $this->addIndex('cart', ['session_id'], 'cart_session_idx');
        $this->addIndex('cart', ['partner_location_address_id', 'id'], 'cart_branch_id_idx');
        $this->addIndex(
            'dining_bills',
            ['partner_id', 'partner_location_id', 'status'],
            'dining_bills_partner_location_status_idx'
        );
        $this->addIndex(
            'dining_payments',
            ['created_at', 'dining_bill_id'],
            'dining_payments_created_bill_idx'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndex('dining_payments', 'dining_payments_created_bill_idx');
        $this->dropIndex('dining_bills', 'dining_bills_partner_location_status_idx');
        $this->dropIndex('cart', 'cart_branch_id_idx');
        $this->dropIndex('cart', 'cart_session_idx');
        $this->dropIndex('order_process', 'order_process_order_status_idx');
        $this->dropIndex('order', 'order_partner_submitted_idx');
    }

    private function addIndex(string $tableName, array $columns, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        foreach ($columns as $column) {
            if (! Schema::hasColumn($tableName, $column)) {
                return;
            }
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName) {
            $table->index($columns, $indexName);
        });
    }

    private function dropIndex(string $tableName, string $indexName): void
    {
        if (! Schema::hasTable($tableName) || ! Schema::hasIndex($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
