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
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('phone_code')->nullable()->change();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
            $table->string('phone_code')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->string('phone_code')->nullable(false)->change();
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->string('phone_code')->nullable(false)->change();
        });
    }
};
