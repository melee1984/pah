<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rider_applications', function (Blueprint $table) {
            $table->char('access_token_hash', 64)->nullable()->unique()->after('reference');
            $table->string('full_name')->nullable()->change();
            $table->string('mobile', 25)->nullable()->change();
            $table->date('birth_date')->nullable()->change();
            $table->text('home_address')->nullable()->change();
            $table->string('profile_photo_path')->nullable()->change();
            $table->string('emergency_contact_name')->nullable()->change();
            $table->string('emergency_contact_relationship', 100)->nullable()->change();
            $table->string('emergency_contact_mobile', 25)->nullable()->change();
            $table->string('government_id_path')->nullable()->change();
            $table->string('drivers_license_path')->nullable()->change();
            $table->string('vehicle_registration_path')->nullable()->change();
            $table->string('vehicle_type', 100)->nullable()->change();
            $table->string('vehicle_make_model')->nullable()->change();
            $table->string('vehicle_plate_number', 50)->nullable()->change();
            $table->string('vehicle_color', 100)->nullable()->change();
            $table->string('payout_method', 100)->nullable()->change();
            $table->string('payout_account_name')->nullable()->change();
            $table->text('payout_account_number')->nullable()->change();
            $table->string('status', 25)->default('draft')->change();
            $table->timestamp('submitted_at')->nullable()->change();
        });

        Schema::create('rider_application_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rider_application_id')
                ->constrained('rider_applications')
                ->cascadeOnDelete();
            $table->uuid('reference')->unique();
            $table->string('type', 50);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();

            $table->unique(['rider_application_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_application_documents');

        Schema::table('rider_applications', function (Blueprint $table) {
            $table->dropUnique(['access_token_hash']);
            $table->dropColumn('access_token_hash');
            $table->string('full_name')->nullable(false)->change();
            $table->string('mobile', 25)->nullable(false)->change();
            $table->date('birth_date')->nullable(false)->change();
            $table->text('home_address')->nullable(false)->change();
            $table->string('profile_photo_path')->nullable(false)->change();
            $table->string('emergency_contact_name')->nullable(false)->change();
            $table->string('emergency_contact_relationship', 100)->nullable(false)->change();
            $table->string('emergency_contact_mobile', 25)->nullable(false)->change();
            $table->string('government_id_path')->nullable(false)->change();
            $table->string('drivers_license_path')->nullable(false)->change();
            $table->string('vehicle_registration_path')->nullable(false)->change();
            $table->string('vehicle_type', 100)->nullable(false)->change();
            $table->string('vehicle_make_model')->nullable(false)->change();
            $table->string('vehicle_plate_number', 50)->nullable(false)->change();
            $table->string('vehicle_color', 100)->nullable(false)->change();
            $table->string('payout_method', 100)->nullable(false)->change();
            $table->string('payout_account_name')->nullable(false)->change();
            $table->text('payout_account_number')->nullable(false)->change();
            $table->string('status', 25)->default('pending')->change();
            $table->timestamp('submitted_at')->nullable(false)->change();
        });
    }
};
