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
        Schema::table('customer_invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_invoices', 'payment_receipt')) {
                $table->string('payment_receipt')->nullable()->after('payment_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('customer_invoices', 'payment_receipt')) {
                $table->dropColumn('payment_receipt');
            }
        });
    }
};
