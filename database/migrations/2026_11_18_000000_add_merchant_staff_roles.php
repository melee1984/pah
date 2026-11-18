<?php

use App\Support\MerchantStaffRoles;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        MerchantStaffRoles::seed();
    }

    public function down(): void
    {
        // Preserve assigned staff roles and their permissions on rollback.
    }
};
