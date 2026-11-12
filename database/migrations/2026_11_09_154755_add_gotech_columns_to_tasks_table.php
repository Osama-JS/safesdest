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
            $table->string('gotech_task_id')->nullable()->after('is_escrow')->index();
            $table->string('gotech_task_number')->nullable()->after('gotech_task_id')->index();
            $table->string('gotech_status')->nullable()->after('gotech_task_number')->index();
            $table->decimal('gotech_funding_amount', 12, 2)->nullable()->after('gotech_status');
            $table->timestamp('gotech_funded_at')->nullable()->after('gotech_funding_amount');
            $table->json('gotech_metadata')->nullable()->after('gotech_funded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn([
                'gotech_task_id',
                'gotech_task_number',
                'gotech_status',
                'gotech_funding_amount',
                'gotech_funded_at',
                'gotech_metadata',
            ]);
        });
    }
};
