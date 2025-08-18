<?php

namespace App\Schedule;

use App\Models\Task;
use App\Jobs\DistributeTask;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Log;

class DriverScheduler
{
    public function __invoke(Schedule $schedule): void
    {
        $schedule->call(function () {
            Log::info('🔄 Running scheduler...');
            $tasks = Task::where('status', 'in_progress')->where('driver_id', null)->where('distribution_attempts', '<', 5)
              ->get();
            Log::info('📝 Found ' . $tasks->count() . ' tasks to distribute.');

            $tasks->each(function ($task) {
                Log::info('📤 Dispatching job for task #' . $task->id);
                DistributeTask::dispatch($task);
            });
        })->everyMinute();
    }
}
