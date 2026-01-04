<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_broadcast')->default(false)->after('pending_driver_id');
        });

        Schema::table('settings', function (Blueprint $table) {
            if (!Schema::hasColumn('settings', 'type')) {
                $table->string('type')->default('text')->after('value');
            }
            if (!Schema::hasColumn('settings', 'category')) {
                $table->string('category')->nullable()->after('type');
            }
            if (!Schema::hasColumn('settings', 'name')) {
                $table->string('name')->nullable()->after('category');
            }
            if (!Schema::hasColumn('settings', 'options')) {
                $table->json('options')->nullable()->after('name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('is_broadcast');
        });

        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['type', 'category', 'name', 'options']);
        });
    }
};
