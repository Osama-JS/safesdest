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
            $table->string('payment_method')->nullable()->after('payment_request_number');
            $table->unsignedBigInteger('transaction_id')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('team_wallet_payment_request_logs', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'transaction_id']);
        });
    }
};
