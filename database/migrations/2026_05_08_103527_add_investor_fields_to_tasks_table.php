<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('investor_id')->nullable()->after('user_id');
            $table->enum('investor_payment_status', ['none', 'paid', 'pending'])->default('none')->after('investor_id');
            $table->foreign('investor_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['investor_id', 'investor_payment_status']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['investor_id']);
            $table->dropIndex(['investor_id', 'investor_payment_status']);
            $table->dropColumn(['investor_id', 'investor_payment_status']);
        });
    }
};
