<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('partner_location_tables')) {
            Schema::create('partner_location_tables', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_location_id')->index();
                $table->string('name', 100);
                $table->unsignedInteger('capacity')->default(1);
                $table->boolean('active')->default(true);
                $table->boolean('is_available')->default(true);
                $table->timestamps();

                $table->unique(['partner_location_id', 'name']);
            });
        }

        if (Schema::hasTable('cart')) {
            Schema::table('cart', function (Blueprint $table) {
                if (! Schema::hasColumn('cart', 'fulfillment_type')) {
                    $table->string('fulfillment_type', 20)->default('delivery')->after('partner_location_address_id');
                }

                if (! Schema::hasColumn('cart', 'dining_table_id')) {
                    $table->unsignedBigInteger('dining_table_id')->nullable()->after('fulfillment_type')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('cart')) {
            Schema::table('cart', function (Blueprint $table) {
                if (Schema::hasColumn('cart', 'dining_table_id')) {
                    $table->dropColumn('dining_table_id');
                }

                if (Schema::hasColumn('cart', 'fulfillment_type')) {
                    $table->dropColumn('fulfillment_type');
                }
            });
        }

        Schema::dropIfExists('partner_location_tables');
    }
};
