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
        Schema::table('team_wallet_payment_request_logs', function (Blueprint $table) {
            $table->string('payment_request_number', 50)->nullable()->after('amount')->comment('رقم طلب السداد المطبوع');
            $table->index('payment_request_number', 'team_payment_request_number_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_wallet_payment_request_logs', function (Blueprint $table) {
            $table->dropIndex('team_payment_request_number_index');
            $table->dropColumn('payment_request_number');
        });
    }
};
