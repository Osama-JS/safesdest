<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Dropping the PostgreSQL check constraints created by the original ENUM definition
        try {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_payment_method_check");
        } catch (\Exception $e) {}

        try {
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
        } catch (\Exception $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Not adding them back safely
    }
};
