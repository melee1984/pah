<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            if (! Schema::hasColumn('agents', 'review_status')) {
                $table->string('review_status', 20)->nullable()->index();
            }

            if (! Schema::hasColumn('agents', 'review_message')) {
                $table->text('review_message')->nullable();
            }

            if (! Schema::hasColumn('agents', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('agents')) {
            return;
        }

        Schema::table('agents', function (Blueprint $table) {
            $columns = collect([
                'review_status',
                'review_message',
                'reviewed_at',
            ])->filter(fn ($column) => Schema::hasColumn('agents', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
