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
        Schema::table('users', function (Blueprint $table) {
            $table->string('bank_name')->nullable()->after('additional_data')->comment('اسم البنك');
            $table->string('account_number')->nullable()->after('bank_name')->comment('رقم الحساب البنكي');
            $table->string('iban_number')->nullable()->after('account_number')->comment('رقم الآيبان');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'account_number', 'iban_number']);
        });
    }
};
