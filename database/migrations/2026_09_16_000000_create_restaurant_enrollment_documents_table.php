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
                if (! Schema::hasColumn('partners', 'business_structure')) {
                    $table->string('business_structure', 30)->nullable();
                }

                if (! Schema::hasColumn('partners', 'enrolling_as')) {
                    $table->string('enrolling_as', 30)->nullable();
                }
            });
        }

        if (! Schema::hasTable('restaurant_enrollment_documents')) {
            Schema::create('restaurant_enrollment_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('partner_id');
                $table->string('document_type', 50);
                $table->string('file_path', 500);
                $table->string('original_name');
                $table->timestamps();

                $table->unique(
                    ['partner_id', 'document_type'],
                    'restaurant_enrollment_documents_partner_type_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_enrollment_documents');

        if (! Schema::hasTable('partners')) {
            return;
        }

        Schema::table('partners', function (Blueprint $table) {
            $columns = collect([
                'business_structure',
                'enrolling_as',
            ])->filter(fn ($column) => Schema::hasColumn('partners', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
