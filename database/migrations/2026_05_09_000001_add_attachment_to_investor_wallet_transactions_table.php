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
        if (Schema::hasTable('investor_wallet_transactions')) {
            Schema::table('investor_wallet_transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('investor_wallet_transactions', 'attachment')) {
                    $table->string('attachment')->nullable()->after('description');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('investor_wallet_transactions')) {
            Schema::table('investor_wallet_transactions', function (Blueprint $table) {
                if (Schema::hasColumn('investor_wallet_transactions', 'attachment')) {
                    $table->dropColumn('attachment');
                }
            });
        }
    }
};
