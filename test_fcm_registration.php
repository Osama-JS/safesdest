<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Driver;
use Illuminate\Support\Facades\DB;

echo "🔍 Testing FCM Token Registration After Fix\n";
echo str_repeat("=", 60) . "\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✅ Database connection: OK\n\n";

    // Check drivers table structure
    echo "📋 Checking drivers table structure...\n";
    $columns = DB::select("DESCRIBE drivers");
    $hasColumns = [];
    
    foreach ($columns as $column) {
        if (in_array($column->Field, ['fcm_token', 'device_id', 'last_activity_at'])) {
            $hasColumns[] = $column->Field;
            echo "   ✅ Column '{$column->Field}': {$column->Type}\n";
        }
    }
    
    if (count($hasColumns) === 3) {
        echo "   ✅ All required columns present\n\n";
    } else {
        echo "   ❌ Missing columns: " . implode(', ', array_diff(['fcm_token', 'device_id', 'last_activity_at'], $hasColumns)) . "\n\n";
    }

    // Check current FCM tokens
    echo "📊 Current FCM Token Status:\n";
    $totalDrivers = Driver::count();
    $driversWithTokens = Driver::whereNotNull('fcm_token')->count();
    $driversWithRecentActivity = Driver::where('last_activity_at', '>=', now()->subDays(7))->count();
    
    echo "   📈 Total drivers: {$totalDrivers}\n";
    echo "   🔑 Drivers with FCM tokens: {$driversWithTokens}\n";
    echo "   🕐 Drivers active in last 7 days: {$driversWithRecentActivity}\n\n";

    if ($driversWithTokens > 0) {
        echo "📱 Recent FCM Token Registrations:\n";
        $recentTokens = Driver::whereNotNull('fcm_token')
                             ->orderBy('last_activity_at', 'desc')
                             ->limit(5)
                             ->get(['id', 'name', 'fcm_token', 'last_activity_at', 'device_id']);
        
        foreach ($recentTokens as $driver) {
            $tokenPreview = $driver->fcm_token ? substr($driver->fcm_token, 0, 20) . '...' : 'null';
            $lastActivity = $driver->last_activity_at ? $driver->last_activity_at->diffForHumans() : 'never';
            echo "   👤 {$driver->name} (ID: {$driver->id})\n";
            echo "      🔑 Token: {$tokenPreview}\n";
            echo "      📱 Device: " . ($driver->device_id ?: 'not set') . "\n";
            echo "      🕐 Last activity: {$lastActivity}\n\n";
        }
    } else {
        echo "⚠️  No drivers with FCM tokens found.\n";
        echo "📝 This could mean:\n";
        echo "   1. No driver has logged in from the Flutter app yet\n";
        echo "   2. Firebase is not properly initialized in the Flutter app\n";
        echo "   3. FCM token is not being sent during login\n\n";
    }

    // Check API endpoint
    echo "🔗 Checking FCM Token Update API:\n";
    $routeExists = false;
    try {
        $routes = \Illuminate\Support\Facades\Route::getRoutes();
        foreach ($routes as $route) {
            if (str_contains($route->uri(), 'fcm-token') && in_array('POST', $route->methods())) {
                echo "   ✅ FCM Token API endpoint found: POST /{$route->uri()}\n";
                $routeExists = true;
                break;
            }
        }
        
        if (!$routeExists) {
            echo "   ❌ FCM Token API endpoint not found\n";
        }
    } catch (Exception $e) {
        echo "   ⚠️  Could not check routes: " . $e->getMessage() . "\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 NEXT STEPS TO TEST:\n";
    echo "1. Open Flutter app and login as a driver\n";
    echo "2. Check console logs for FCM token generation\n";
    echo "3. Run this script again to see if token was saved\n";
    echo "4. Test sending notification from Laravel\n";

    echo "\n📋 DEBUGGING TIPS:\n";
    echo "• Check Flutter console for 'FCM Token:' messages\n";
    echo "• Check Laravel logs for FCM token update requests\n";
    echo "• Verify Firebase is properly initialized in Flutter\n";
    echo "• Ensure device has internet connection\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📝 Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n✨ Test completed!\n";
