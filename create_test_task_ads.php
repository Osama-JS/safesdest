<?php

require_once 'bootstrap/app.php';

use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Driver;

echo "=== Creating Test Task Ads ===\n\n";

// Get tasks that can have ads
$tasks = Task::whereIn('status', ['advertised', 'in_progress'])
    ->where('vehicle_size_id', 1)
    ->get();

echo "Found " . $tasks->count() . " tasks that can have ads\n\n";

foreach ($tasks as $task) {
    echo "Task ID: {$task->id}, Status: {$task->status}, Vehicle Size ID: {$task->vehicle_size_id}\n";
    
    // Check if task ad already exists
    $existingAd = Task_Ad::where('task_id', $task->id)->first();
    
    if ($existingAd) {
        echo "  Task Ad already exists: ID {$existingAd->id}, Status: {$existingAd->status}\n";
        
        // Update to running if not already
        if ($existingAd->status !== 'running') {
            $existingAd->status = 'running';
            $existingAd->save();
            echo "  Updated Task Ad status to 'running'\n";
        }
    } else {
        // Create new task ad
        $taskAd = Task_Ad::create([
            'task_id' => $task->id,
            'description' => 'Task advertisement for Task #' . $task->id,
            'status' => 'running',
            'lowest_price' => $task->total_price * 0.8, // 80% of task price
            'highest_price' => $task->total_price * 1.2, // 120% of task price
            'included' => true,
            'service_commission' => 15.0,
            'service_commission_type' => 'percentage',
            'vat_commission' => 0.0,
        ]);
        
        echo "  Created new Task Ad: ID {$taskAd->id}\n";
    }
    
    echo "---\n";
}

// Check final count
$totalAds = Task_Ad::count();
$runningAds = Task_Ad::where('status', 'running')->count();

echo "\nFinal counts:\n";
echo "Total Task Ads: $totalAds\n";
echo "Running Task Ads: $runningAds\n";

// Test the query from controller
$driver = Driver::first();
if ($driver) {
    echo "\nTesting controller query for driver {$driver->name} (Vehicle Size ID: {$driver->vehicle_size_id}):\n";
    
    $availableAds = Task_Ad::where('status', 'running')
        ->whereHas('task', function ($query) use ($driver) {
            $query->where('vehicle_size_id', $driver->vehicle_size_id)
                  ->whereIn('status', ['advertised', 'in_progress']);
        })
        ->whereDoesntHave('offers', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id);
        })
        ->get();
    
    echo "Available ads for driver: " . $availableAds->count() . "\n";
    
    foreach ($availableAds as $ad) {
        echo "  Ad ID: {$ad->id}, Task ID: {$ad->task_id}\n";
    }
}

echo "\n=== Task Ads Creation Complete ===\n";
