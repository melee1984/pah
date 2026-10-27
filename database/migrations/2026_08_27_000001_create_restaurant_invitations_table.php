<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('restaurant_invitations')) {
            Schema::create('restaurant_invitations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('restaurant_id')->unique();
                $table->string('email')->index();
                $table->char('token_hash', 64)->unique();
                $table->timestamp('expires_at')->index();
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_invitations');
    }
};
