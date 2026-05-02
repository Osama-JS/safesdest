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
        Schema::table('drivers', function (Blueprint $table) {
            $table->string('bic_code')->nullable()->after('iban_number')->comment('رمز معرف البنك - BIC/SWIFT');
            $table->string('beneficiary_name')->nullable()->after('bic_code')->comment('اسم المستفيد الرسمي');
            $table->string('bank_address1')->nullable()->after('beneficiary_name')->comment('العنوان 1 (الشارع)');
            $table->string('bank_address2')->nullable()->after('bank_address1')->comment('العنوان 2 (تفاصيل إضافية)');
            $table->string('bank_city')->nullable()->after('bank_address2')->comment('المدينة');
            $table->string('bank_country')->default('SA')->after('bank_city')->comment('الدولة (رمز ISO)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn([
                'bic_code', 
                'beneficiary_name', 
                'bank_address1', 
                'bank_address2', 
                'bank_city', 
                'bank_country'
            ]);
        });
    }
};
