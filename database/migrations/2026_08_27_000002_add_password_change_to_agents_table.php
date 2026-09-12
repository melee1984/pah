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
            if (! Schema::hasColumn('agents', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->index();
            }
            if (! Schema::hasColumn('agents', 'temporary_password_created_at')) {
                $table->timestamp('temporary_password_created_at')->nullable();
            }
            if (! Schema::hasColumn('agents', 'password_changed_at')) {
                $table->timestamp('password_changed_at')->nullable();
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
                'must_change_password',
                'temporary_password_created_at',
                'password_changed_at',
            ])->filter(fn ($column) => Schema::hasColumn('agents', $column))->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
