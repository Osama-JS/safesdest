<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investor_wallets', function (Blueprint $table) {
            $table->id()->startingValue(1000);
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('restrict');
            $table->boolean('status')->default(true);
            $table->text('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();

            // محفظة استثمار واحدة لكل مستثمر
            $table->unique(['user_id', 'deleted_at'], 'investor_wallet_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investor_wallets');
    }
};
