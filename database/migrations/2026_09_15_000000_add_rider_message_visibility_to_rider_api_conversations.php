<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_api_conversations', function (Blueprint $table) {
            $table->timestamp('rider_hidden_at')->nullable()->after('closed_at');
            $table->timestamp('rider_cleared_at')->nullable()->after('rider_hidden_at');
            $table->unsignedBigInteger('rider_cleared_message_id')->nullable()->after('rider_cleared_at');
        });
    }

    public function down(): void
    {
        Schema::table('rider_api_conversations', function (Blueprint $table) {
            $table->dropColumn([
                'rider_hidden_at',
                'rider_cleared_at',
                'rider_cleared_message_id',
            ]);
        });
    }
};
