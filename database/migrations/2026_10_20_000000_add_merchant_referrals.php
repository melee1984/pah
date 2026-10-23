<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('agents') && ! Schema::hasColumn('agents', 'referral_code')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('referral_code', 20)->nullable()->unique()->after('mobile');
            });

            DB::table('agents')
                ->select('id')
                ->orderBy('id')
                ->each(function ($agent) {
                    DB::table('agents')->where('id', $agent->id)->update([
                        'referral_code' => 'PAH-'.str_pad(strtoupper(base_convert((string) $agent->id, 10, 36)), 8, '0', STR_PAD_LEFT),
                    ]);
                });
        }

        if (Schema::hasTable('merchant_applications') && ! Schema::hasColumn('merchant_applications', 'agent_id')) {
            Schema::table('merchant_applications', function (Blueprint $table) {
                $table->unsignedBigInteger('agent_id')->nullable()->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('merchant_applications') && Schema::hasColumn('merchant_applications', 'agent_id')) {
            Schema::table('merchant_applications', function (Blueprint $table) {
                $table->dropColumn('agent_id');
            });
        }

        if (Schema::hasTable('agents') && Schema::hasColumn('agents', 'referral_code')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('referral_code');
            });
        }
    }
};
