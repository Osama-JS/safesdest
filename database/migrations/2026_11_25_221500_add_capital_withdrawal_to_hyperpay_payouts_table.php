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
            if (!Schema::hasColumn('hyperpay_payouts', 'investor_wallet_id')) {
                $table->unsignedBigInteger('investor_wallet_id')->nullable()->after('user_wallet_id');
                $table->foreign('investor_wallet_id')->references('id')->on('investor_wallets')->onDelete('set null');
            }

            if (!Schema::hasColumn('hyperpay_payouts', 'source_capital_withdrawal_id')) {
                $table->unsignedBigInteger('source_capital_withdrawal_id')->nullable()->after('source_commission_withdrawal_id');
                $table->foreign('source_capital_withdrawal_id')->references('id')->on('investor_capital_withdrawals')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hyperpay_payouts', function (Blueprint $table) {
            if (Schema::hasColumn('hyperpay_payouts', 'source_capital_withdrawal_id')) {
                $table->dropForeign(['source_capital_withdrawal_id']);
                $table->dropColumn('source_capital_withdrawal_id');
            }

            if (Schema::hasColumn('hyperpay_payouts', 'investor_wallet_id')) {
                $table->dropForeign(['investor_wallet_id']);
                $table->dropColumn('investor_wallet_id');
            }
        });
    }
};
