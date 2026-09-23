<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('restaurant_reviews')) {
            return;
        }

        Schema::create('restaurant_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('partner_id')->index();
            $table->unsignedBigInteger('partner_location_id')->nullable()->index();
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['user_id', 'partner_id', 'partner_location_id'],
                'restaurant_reviews_user_partner_location_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_reviews');
    }
};
