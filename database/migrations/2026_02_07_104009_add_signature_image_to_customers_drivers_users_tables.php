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
            $table->string('signature_image')->nullable()->after('image');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->string('signature_image')->nullable()->after('image');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_image')->nullable()->after('profile_photo_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('signature_image');
        });

        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn('signature_image');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('signature_image');
        });
    }
};
