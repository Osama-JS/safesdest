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
    Schema::create('geofences_has_team', function (Blueprint $table) {
      $table->id();
      $table->foreignId('geofence_id')->constrained('geofences')->onDelete('cascade');
      $table->foreignId('team_id')->constrained('teams')->onDelete('cascade');
      $table->unique(['geofence_id', 'team_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('geofences_has_team');
  }
};
