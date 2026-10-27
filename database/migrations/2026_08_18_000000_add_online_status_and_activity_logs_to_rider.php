<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('rider', 'is_active')) {
            Schema::table('rider', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('active')->index();
            });
        }

        if (! Schema::hasTable('rider_api_activity_logs')) {
            Schema::create('rider_api_activity_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('rider_id')->index();
                $table->string('type', 20)->index();
                $table->timestamp('recorded_at')->index();
                $table->timestamps();

                $table->index(['rider_id', 'recorded_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_api_activity_logs');

        if (Schema::hasColumn('rider', 'is_active')) {
            Schema::table('rider', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
