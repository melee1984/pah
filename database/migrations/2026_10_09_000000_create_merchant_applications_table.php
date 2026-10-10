<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('merchant_applications')) {
            Schema::create('merchant_applications', function (Blueprint $table) {
                $table->id();
                $table->string('business_name');
                $table->string('registered_business_name');
                $table->string('owner_name');
                $table->string('email')->unique();
                $table->string('mobile', 30);
                $table->string('telephone', 30)->nullable();
                $table->text('address');
                $table->string('city', 120);
                $table->string('business_structure', 30);
                $table->string('cuisine', 120)->nullable();
                $table->unsignedSmallInteger('branch_count')->default(1);
                $table->json('services');
                $table->string('website')->nullable();
                $table->text('business_description')->nullable();
                $table->decimal('commission_percentage', 5, 2);
                $table->string('status', 20)->default('pending')->index();
                $table->text('review_message')->nullable();
                $table->unsignedBigInteger('reviewed_by')->nullable()->index();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_applications');
    }
};
