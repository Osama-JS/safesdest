<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds driver_price (nullable) to pricing_parametars.
     * When set for a "points" type route, the platform commission
     * will be calculated as (price - driver_price) instead of
     * the global template commission.
     */
    public function up(): void
    {
        Schema::table('pricing_parametars', function (Blueprint $table) {
            $table->decimal('driver_price', 10, 2)->nullable()->after('price')
                ->comment('سعر السائق للمسار. إذا كان موجوداً، تُحسب العمولة = price - driver_price وتُتجاهل عمولة القالب العامة.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pricing_parametars', function (Blueprint $table) {
            $table->dropColumn('driver_price');
        });
    }
};
