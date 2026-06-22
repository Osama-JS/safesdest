<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hyperpay_payouts', function (Blueprint $table) {
            $table->json('webhook_payload')->nullable()->after('failure_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hyperpay_payouts', function (Blueprint $table) {
            $table->dropColumn('webhook_payload');
        });
    }
};
