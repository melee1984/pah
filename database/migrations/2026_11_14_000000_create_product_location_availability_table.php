<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_location_availability')
            || ! Schema::hasTable('products')
            || ! Schema::hasTable('partner_location')) {
            return;
        }

        Schema::create('product_location_availability', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->index();
            $table->unsignedBigInteger('partner_location_id')->index();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(
                ['product_id', 'partner_location_id'],
                'product_location_availability_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_location_availability');
    }
};
