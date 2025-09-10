<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Driver;

class CreateTestTaskAds extends Command
{
    protected $signature = 'test:create-task-ads';
    protected $description = 'Create test task ads for debugging';

    public function handle()
    {
        $this->info('Creating test task ads...');

        // Get tasks that can have ads
        $tasks = Task::whereIn('status', ['advertised', 'in_progress'])
            ->where('vehicle_size_id', 1)
            ->get();

        $this->info("Found {$tasks->count()} tasks that can have ads");

        foreach ($tasks as $task) {
            $this->line("Task ID: {$task->id}, Status: {$task->status}, Vehicle Size ID: {$task->vehicle_size_id}");

            // Check if task ad already exists
            $existingAd = Task_Ad::where('task_id', $task->id)->first();

            if ($existingAd) {
                $this->line("  Task Ad already exists: ID {$existingAd->id}, Status: {$existingAd->status}");

                // Update to running if not already
                if ($existingAd->status !== 'running') {
                    $existingAd->status = 'running';
                    $existingAd->save();
                    $this->line("  Updated Task Ad status to 'running'");
                }
            } else {
                // Create new task ad
                $taskAd = Task_Ad::create([
                    'task_id' => $task->id,
                    'description' => 'Task advertisement for Task #' . $task->id,
                    'status' => 'running',
                    'lowest_price' => 100.0, // Simple fixed price
                    'highest_price' => 200.0, // Simple fixed price
                ]);

                $this->line("  Created new Task Ad: ID {$taskAd->id}");
            }
        }

        // Check final count
        $totalAds = Task_Ad::count();
        $runningAds = Task_Ad::where('status', 'running')->count();

        $this->info("Final counts:");
        $this->info("Total Task Ads: $totalAds");
        $this->info("Running Task Ads: $runningAds");

        // Test the query from controller
        $driver = Driver::first();
        if ($driver) {
            $this->info("Testing controller query for driver {$driver->name} (Vehicle Size ID: {$driver->vehicle_size_id}):");

            $availableAds = Task_Ad::where('status', 'running')
                ->whereHas('task', function ($query) use ($driver) {
                    $query->where('vehicle_size_id', $driver->vehicle_size_id)
                          ->whereIn('status', ['advertised', 'in_progress']);
                })
                ->whereDoesntHave('offers', function ($query) use ($driver) {
                    $query->where('driver_id', $driver->id);
                })
                ->get();

            $this->info("Available ads for driver: " . $availableAds->count());

            foreach ($availableAds as $ad) {
                $this->line("  Ad ID: {$ad->id}, Task ID: {$ad->task_id}");
            }
        }

        $this->info('Task ads creation complete!');
        return 0;
    }
}
