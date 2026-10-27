<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partner_promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('cta_label')->nullable();
            $table->string('link_url')->nullable();
            $table->string('image_path');
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->dateTime('starts_at')->nullable()->index();
            $table->dateTime('ends_at')->nullable()->index();
            $table->timestamps();

            $table->index(['active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('partner_promotions');
    }
};
