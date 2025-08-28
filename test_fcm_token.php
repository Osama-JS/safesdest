<?php

// Simple test to check FCM token registration
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Driver;

echo "🔍 Testing FCM Token Registration...\n\n";

try {
    // Check if we have any drivers with FCM tokens
    $driversWithTokens = Driver::whereNotNull('fcm_token')->get();
    
    echo "📊 Drivers with FCM tokens: " . $driversWithTokens->count() . "\n\n";
    
    if ($driversWithTokens->count() > 0) {
        echo "✅ Found drivers with FCM tokens:\n";
        foreach ($driversWithTokens as $driver) {
            echo "   - ID: {$driver->id}, Name: {$driver->name}\n";
            echo "     Token: " . substr($driver->fcm_token, 0, 20) . "...\n";
            echo "     Last Activity: " . ($driver->last_activity_at ?: 'Never') . "\n\n";
        }
    } else {
        echo "⚠️  No drivers with FCM tokens found.\n";
        echo "📝 This means:\n";
        echo "   1. No driver has logged in from the Flutter app yet, OR\n";
        echo "   2. Firebase is not properly initialized in the Flutter app\n\n";
        
        // Check total drivers
        $totalDrivers = Driver::count();
        echo "📊 Total drivers in database: {$totalDrivers}\n";
        
        if ($totalDrivers > 0) {
            echo "💡 You can test by:\n";
            echo "   1. Opening the Flutter app\n";
            echo "   2. Logging in as a driver\n";
            echo "   3. The FCM token should be automatically registered\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🔧 Environment Status:\n";
echo "   Database Connection: " . (DB::connection()->getPdo() ? "✅ Connected" : "❌ Failed") . "\n";
echo "   Firebase Project ID: " . (env('FIREBASE_PROJECT_ID') ?: '❌ Not Set') . "\n";
echo "   Firebase Credentials: " . (env('FIREBASE_CREDENTIALS') ?: '❌ Not Set') . "\n";

$credentialsPath = storage_path(env('FIREBASE_CREDENTIALS'));
echo "   Service Account File: " . (file_exists($credentialsPath) ? "✅ Exists" : "❌ Missing") . "\n";

echo "\n🎯 Next Steps:\n";
echo "1. If no FCM tokens: Test Flutter app login\n";
echo "2. If service account missing: Add Firebase credentials\n";
echo "3. If tokens exist: Test sending notifications\n";

echo "\n✨ Test completed!\n";
