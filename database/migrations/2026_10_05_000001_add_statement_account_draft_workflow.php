<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('statement_accounts')) {
            return;
        }

        DB::table('statement_accounts')->where('status', 'issued')->update(['status' => 'published']);

        Schema::table('statement_accounts', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change();
            $table->timestamp('issued_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('statement_accounts')) {
            return;
        }

        DB::table('statement_accounts')->where('status', 'draft')->update([
            'status' => 'issued',
            'issued_at' => DB::raw('COALESCE(issued_at, created_at)'),
        ]);
        DB::table('statement_accounts')->where('status', 'published')->update(['status' => 'issued']);

        Schema::table('statement_accounts', function (Blueprint $table) {
            $table->string('status', 20)->default('issued')->change();
            $table->timestamp('issued_at')->nullable(false)->change();
        });
    }
};
