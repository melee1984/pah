<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Merchant applications now live in the partners table. This historical
        // migration remains in place so existing migration records stay valid.
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_applications');
    }
};
