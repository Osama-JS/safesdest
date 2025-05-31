<?php

namespace App\Schedule;

use App\Models\Driver;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Laravel\Pennant\Feature;

class CheckDriversOnline
{
  public function __invoke(Schedule $schedule): void
  {
    $schedule->call(function () {
      $threshold = now()->subMinutes(3);

      $updatedCount = Driver::where('online', true)
        ->where('last_seen_at', '<', $threshold)
        ->update(['online' => false]);

      logger()->info("DriverStatusScheduler: Updated {$updatedCount} drivers to offline.");
    })->everyMinute();
  }
}
