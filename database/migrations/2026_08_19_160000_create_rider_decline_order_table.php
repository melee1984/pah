<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('rider_decline_order')) {
            Schema::create('rider_decline_order', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rider_id')->index();
                $table->unsignedBigInteger('order_id')->index();
                $table->timestamps();

                $table->unique(['rider_id', 'order_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_decline_order');
    }
};
