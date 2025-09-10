<?php

require_once 'bootstrap/app.php';

use App\Models\Driver;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use App\Models\Task;

echo "=== Debug Task Ads Data ===\n\n";

// Get first driver
$driver = Driver::first();
if (!$driver) {
    echo "❌ No drivers found\n";
    exit(1);
}

echo "✅ Driver: {$driver->name} (ID: {$driver->id})\n";
echo "Vehicle Size: {$driver->vehicle_size}\n\n";

// Check Task Ads
echo "📋 Task Ads Analysis:\n";
echo "--------------------\n";

$allAds = Task_Ad::all();
echo "Total Task Ads: " . $allAds->count() . "\n";

$runningAds = Task_Ad::where('status', 'running')->get();
echo "Running Ads: " . $runningAds->count() . "\n";

$closedAds = Task_Ad::where('status', 'closed')->get();
echo "Closed Ads: " . $closedAds->count() . "\n";

// Show all ads with details
echo "\n📝 All Task Ads Details:\n";
foreach ($allAds as $ad) {
    echo "Ad ID: {$ad->id} | Status: {$ad->status} | Task ID: {$ad->task_id}\n";
    
    // Get related task
    $task = Task::find($ad->task_id);
    if ($task) {
        echo "  Task Status: {$task->status} | Vehicle Size: {$task->vehicle_size}\n";
        echo "  Price Range: {$ad->lowest_price} - {$ad->highest_price}\n";
    } else {
        echo "  ❌ Task not found for Task ID: {$ad->task_id}\n";
    }
    
    // Check offers for this ad
    $offers = Task_Offire::where('task_ad_id', $ad->id)->get();
    echo "  Offers Count: " . $offers->count() . "\n";
    
    foreach ($offers as $offer) {
        echo "    Offer ID: {$offer->id} | Driver: {$offer->driver_id} | Accepted: " . ($offer->accepted ? 'Yes' : 'No') . "\n";
    }
    echo "\n";
}

// Check what ads should be available for this driver
echo "🔍 Available Ads for Driver Analysis:\n";
echo "------------------------------------\n";

// Method 1: Simple running ads
$simpleRunning = Task_Ad::where('status', 'running')->get();
echo "Simple Running Ads: " . $simpleRunning->count() . "\n";

// Method 2: Running ads with vehicle size match
$vehicleMatchAds = Task_Ad::where('status', 'running')
    ->whereHas('task', function ($query) use ($driver) {
        $query->where('vehicle_size', $driver->vehicle_size);
    })->get();
echo "Vehicle Size Match Ads: " . $vehicleMatchAds->count() . "\n";

// Method 3: Running ads without driver's offers
$adsWithoutDriverOffers = Task_Ad::where('status', 'running')
    ->whereDoesntHave('offers', function ($query) use ($driver) {
        $query->where('driver_id', $driver->id);
    })->get();
echo "Ads Without Driver Offers: " . $adsWithoutDriverOffers->count() . "\n";

// Method 4: Complete filter (running + vehicle match + no driver offers)
$completeFilter = Task_Ad::where('status', 'running')
    ->whereHas('task', function ($query) use ($driver) {
        $query->where('vehicle_size', $driver->vehicle_size);
    })
    ->whereDoesntHave('offers', function ($query) use ($driver) {
        $query->where('driver_id', $driver->id);
    })->get();
echo "Complete Filter Ads: " . $completeFilter->count() . "\n";

// Show details of complete filter results
if ($completeFilter->count() > 0) {
    echo "\n📋 Available Ads Details:\n";
    foreach ($completeFilter as $ad) {
        echo "Ad ID: {$ad->id} | Task ID: {$ad->task_id} | Status: {$ad->status}\n";
        $task = $ad->task;
        if ($task) {
            echo "  Task Status: {$task->status} | Vehicle: {$task->vehicle_size}\n";
        }
    }
}

// Check driver's offers
echo "\n🎯 Driver's Offers Analysis:\n";
echo "---------------------------\n";

$allDriverOffers = Task_Offire::where('driver_id', $driver->id)->get();
echo "Total Driver Offers: " . $allDriverOffers->count() . "\n";

$pendingOffers = Task_Offire::where('driver_id', $driver->id)
    ->where('accepted', false)->get();
echo "Pending Offers: " . $pendingOffers->count() . "\n";

$acceptedOffers = Task_Offire::where('driver_id', $driver->id)
    ->where('accepted', true)->get();
echo "Accepted Offers: " . $acceptedOffers->count() . "\n";

echo "\n=== Debug Complete ===\n";
