<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('merchant_branch_users')) {
            return;
        }

        Schema::create('merchant_branch_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedBigInteger('partner_id')->index();
            $table->unsignedBigInteger('partner_location_id')->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();

            $table->unique(
                ['partner_id', 'partner_location_id', 'user_id'],
                'merchant_branch_users_assignment_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_branch_users');
    }
};
