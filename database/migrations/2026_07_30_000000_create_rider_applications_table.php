<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rider_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->string('mobile', 25);
            $table->string('password');
            $table->date('birth_date');
            $table->text('home_address');
            $table->string('profile_photo_path');
            $table->string('emergency_contact_name');
            $table->string('emergency_contact_relationship', 100);
            $table->string('emergency_contact_mobile', 25);
            $table->string('government_id_path');
            $table->string('drivers_license_path');
            $table->string('vehicle_registration_path');
            $table->string('vehicle_type', 100);
            $table->string('vehicle_make_model');
            $table->string('vehicle_plate_number', 50);
            $table->string('vehicle_color', 100);
            $table->string('payout_method', 100);
            $table->string('payout_account_name');
            $table->text('payout_account_number');
            $table->string('status', 25)->default('pending')->index();
            $table->text('review_notes')->nullable();
            $table->timestamp('submitted_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rider_applications');
    }
};
