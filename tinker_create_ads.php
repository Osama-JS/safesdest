// Check existing task ads
$ads = App\Models\Task_Ad::all();
echo "Existing Task Ads: " . $ads->count() . "\n";

// Get tasks that can have ads
$tasks = App\Models\Task::whereIn('status', ['advertised', 'in_progress'])->where('vehicle_size_id', 1)->get();
echo "Tasks available for ads: " . $tasks->count() . "\n";

// Create task ads for each task
foreach ($tasks as $task) {
    $existingAd = App\Models\Task_Ad::where('task_id', $task->id)->first();
    if (!$existingAd) {
        $ad = App\Models\Task_Ad::create([
            'task_id' => $task->id,
            'description' => 'Advertisement for Task #' . $task->id,
            'status' => 'running',
            'lowest_price' => $task->total_price * 0.8,
            'highest_price' => $task->total_price * 1.2,
            'included' => true,
            'service_commission' => 15.0,
            'service_commission_type' => 'percentage',
            'vat_commission' => 0.0,
        ]);
        echo "Created ad ID: " . $ad->id . " for task " . $task->id . "\n";
    } else {
        if ($existingAd->status !== 'running') {
            $existingAd->status = 'running';
            $existingAd->save();
            echo "Updated ad ID: " . $existingAd->id . " to running\n";
        }
    }
}

// Final check
$runningAds = App\Models\Task_Ad::where('status', 'running')->count();
echo "Running ads: " . $runningAds . "\n";

// Test driver query
$driver = App\Models\Driver::first();
if ($driver) {
    $availableAds = App\Models\Task_Ad::where('status', 'running')
        ->whereHas('task', function ($query) use ($driver) {
            $query->where('vehicle_size_id', $driver->vehicle_size_id)
                  ->whereIn('status', ['advertised', 'in_progress']);
        })
        ->whereDoesntHave('offers', function ($query) use ($driver) {
            $query->where('driver_id', $driver->id);
        })
        ->count();
    echo "Available ads for driver " . $driver->name . ": " . $availableAds . "\n";
}
