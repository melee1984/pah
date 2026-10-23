<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('agent_statement_accounts') || Schema::hasColumn('agent_statement_accounts', 'status')) {
            return;
        }

        Schema::table('agent_statement_accounts', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->index()->after('created_by');
            $table->timestamp('issued_at')->nullable()->change();
        });

        DB::table('agent_statement_accounts')->update(['status' => 'published']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('agent_statement_accounts') || ! Schema::hasColumn('agent_statement_accounts', 'status')) {
            return;
        }

        DB::table('agent_statement_accounts')->whereNull('issued_at')->update(['issued_at' => DB::raw('created_at')]);

        Schema::table('agent_statement_accounts', function (Blueprint $table) {
            $table->timestamp('issued_at')->nullable(false)->change();
            $table->dropColumn('status');
        });
    }
};
