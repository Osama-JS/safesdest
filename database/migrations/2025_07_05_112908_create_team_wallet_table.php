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
    Schema::create('team_wallet', function (Blueprint $table) {
      $table->id()->startingValue(1000);
      $table->bigInteger('team_id')->nullable();
      $table->foreign('team_id')->references('id')->on('teams')->onDelete('restrict');
      $table->softDeletes();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('team_wallet');
  }
};
