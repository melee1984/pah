<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_method')) {
            return;
        }

        $addApplication = ! Schema::hasColumn('payment_method', 'available_in_application');
        $addQr = ! Schema::hasColumn('payment_method', 'available_in_qr');

        if ($addApplication || $addQr) {
            Schema::table('payment_method', function (Blueprint $table) use ($addApplication, $addQr) {
                if ($addApplication) {
                    $table->boolean('available_in_application')->default(true)->after('active');
                }

                if ($addQr) {
                    $table->boolean('available_in_qr')->default(true)->after('available_in_application');
                }
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('payment_method')) {
            return;
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('payment_method', 'available_in_application') ? 'available_in_application' : null,
            Schema::hasColumn('payment_method', 'available_in_qr') ? 'available_in_qr' : null,
        ]));

        if ($columns !== []) {
            Schema::table('payment_method', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }
};
