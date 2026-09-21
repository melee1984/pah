<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('partners')) {
            Schema::table('partners', function (Blueprint $table) {
                if (! Schema::hasColumn('partners', 'registered_business_name')) {
                    $table->string('registered_business_name')->nullable();
                }

                if (! Schema::hasColumn('partners', 'tin')) {
                    $table->string('tin', 30)->nullable();
                }

                if (! Schema::hasColumn('partners', 'business_registration_number')) {
                    $table->string('business_registration_number', 100)->nullable();
                }

                if (! Schema::hasColumn('partners', 'payout_account_name')) {
                    $table->string('payout_account_name')->nullable();
                }

                if (! Schema::hasColumn('partners', 'application_status')) {
                    $table->string('application_status', 30)->nullable();
                }

                if (! Schema::hasColumn('partners', 'application_remarks')) {
                    $table->text('application_remarks')->nullable();
                }
            });
        }

        if (Schema::hasTable('restaurant_enrollment_documents')) {
            Schema::table('restaurant_enrollment_documents', function (Blueprint $table) {
                if (! Schema::hasColumn('restaurant_enrollment_documents', 'status')) {
                    $table->string('status', 30)->default('pending_verification');
                }

                if (! Schema::hasColumn('restaurant_enrollment_documents', 'remarks')) {
                    $table->text('remarks')->nullable();
                }

                if (! Schema::hasColumn('restaurant_enrollment_documents', 'expires_at')) {
                    $table->date('expires_at')->nullable();
                }

                if (! Schema::hasColumn('restaurant_enrollment_documents', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        $this->dropColumnsIfPresent('restaurant_enrollment_documents', [
            'status',
            'remarks',
            'expires_at',
            'reviewed_at',
        ]);

        $this->dropColumnsIfPresent('partners', [
            'registered_business_name',
            'tin',
            'business_registration_number',
            'payout_account_name',
            'application_status',
            'application_remarks',
        ]);
    }

    private function dropColumnsIfPresent(string $tableName, array $columns): void
    {
        if (! Schema::hasTable($tableName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
            $existingColumns = collect($columns)
                ->filter(fn ($column) => Schema::hasColumn($tableName, $column))
                ->all();

            if ($existingColumns !== []) {
                $table->dropColumn($existingColumns);
            }
        });
    }
};
