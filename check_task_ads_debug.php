<?php

require_once 'bootstrap/app.php';

use App\Models\Driver;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use App\Models\Task;

echo "=== Task Ads Debug Analysis ===\n\n";

// Get first driver
$driver = Driver::first();
if (!$driver) {
    echo "❌ No drivers found\n";
    exit(1);
}

echo "✅ Driver Info:\n";
echo "ID: {$driver->id}\n";
echo "Name: {$driver->name}\n";
echo "Vehicle Size ID: " . ($driver->vehicle_size_id ?? 'NULL') . "\n";
echo "Vehicle Size: " . ($driver->vehicle_size ?? 'NULL') . "\n\n";

// Check all task ads
$allAds = Task_Ad::with('task')->get();
echo "📋 Total Task Ads: " . $allAds->count() . "\n\n";

foreach ($allAds as $ad) {
    echo "Ad ID: {$ad->id}\n";
    echo "Status: {$ad->status}\n";
    echo "Task ID: {$ad->task_id}\n";
    
    if ($ad->task) {
        echo "Task Status: {$ad->task->status}\n";
        echo "Task Vehicle Size ID: " . ($ad->task->vehicle_size_id ?? 'NULL') . "\n";
        echo "Task Vehicle Size: " . ($ad->task->vehicle_size ?? 'NULL') . "\n";
        
        // Check if vehicle size matches
        $vehicleMatch = ($ad->task->vehicle_size_id == $driver->vehicle_size_id);
        echo "Vehicle Size Match: " . ($vehicleMatch ? 'YES' : 'NO') . "\n";
    } else {
        echo "❌ Task not found\n";
    }
    
    // Check offers for this ad
    $offers = Task_Offire::where('task_ad_id', $ad->id)->get();
    echo "Total Offers: " . $offers->count() . "\n";
    
    $driverOffers = $offers->where('driver_id', $driver->id);
    echo "Driver Offers: " . $driverOffers->count() . "\n";
    
    echo "Should Show to Driver: ";
    if ($ad->status == 'running' && 
        $ad->task && 
        $ad->task->vehicle_size_id == $driver->vehicle_size_id && 
        $driverOffers->count() == 0) {
        echo "YES ✅\n";
    } else {
        echo "NO ❌\n";
        if ($ad->status != 'running') echo "  - Ad not running\n";
        if (!$ad->task) echo "  - Task not found\n";
        if ($ad->task && $ad->task->vehicle_size_id != $driver->vehicle_size_id) echo "  - Vehicle size mismatch\n";
        if ($driverOffers->count() > 0) echo "  - Driver already has offer\n";
    }
    
    echo "---\n\n";
}

// Test the exact query from controller
echo "🔍 Controller Query Test:\n";
echo "------------------------\n";

$availableAds = Task_Ad::where('status', 'running')
    ->whereHas('task', function ($query) use ($driver) {
        $query->where('vehicle_size_id', $driver->vehicle_size_id);
    })
    ->whereDoesntHave('offers', function ($query) use ($driver) {
        $query->where('driver_id', $driver->id);
    })
    ->get();

echo "Available Ads Count: " . $availableAds->count() . "\n";

if ($availableAds->count() > 0) {
    echo "Available Ads:\n";
    foreach ($availableAds as $ad) {
        echo "- Ad ID: {$ad->id}, Task ID: {$ad->task_id}\n";
    }
}

echo "\n=== Debug Complete ===\n";
