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
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('customer_cancel')->default(false);
            $table->string('customer_cancel_reason')->nullable();

            $table->boolean('driver_cancel')->default(false);
            $table->string('driver_cancel_reason')->nullable();

        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['customer_cancel', 'customer_cancel_reason', 'driver_cancel', 'driver_cancel_reason']);
        });
    }
};
