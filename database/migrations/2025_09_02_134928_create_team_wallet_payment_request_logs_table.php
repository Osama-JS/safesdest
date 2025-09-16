<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('team_wallet_payment_request_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_wallet_id');
            $table->unsignedBigInteger('user_id'); // المستخدم الذي طبع الطلب
            $table->unsignedBigInteger('team_id'); // الفريق
            $table->unsignedBigInteger('team_leader_id'); // رئيس الفريق
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('printed_at');
            $table->timestamps();

            // Foreign Keys
            $table->foreign('team_wallet_id')->references('id')->on('team_wallet')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('team_leader_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes للأداء
            $table->index(['team_wallet_id', 'printed_at']);
            $table->index(['team_id', 'printed_at']);
            $table->index(['team_leader_id', 'printed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('team_wallet_payment_request_logs');
    }
};
