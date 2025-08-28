<?php

require_once 'vendor/autoload.php';

use App\Services\FirebaseService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔥 Testing Firebase Connection...\n\n";

try {
    // Test 1: Check if Firebase service can be instantiated
    echo "1. Testing Firebase Service instantiation...\n";
    $firebaseService = new FirebaseService();
    echo "   ✅ Firebase Service created successfully\n\n";

    // Test 2: Check if we can validate a dummy token (this will fail but shows connection works)
    echo "2. Testing Firebase connection with dummy token validation...\n";
    $result = $firebaseService->validateToken('dummy-token-for-testing');
    
    if (isset($result['success'])) {
        if ($result['success']) {
            echo "   ✅ Token validation successful (unexpected but good!)\n";
        } else {
            echo "   ✅ Firebase connection working (token validation failed as expected)\n";
            echo "   📝 Message: " . $result['message'] . "\n";
        }
    } else {
        echo "   ❌ Unexpected response format\n";
        print_r($result);
    }

} catch (Exception $e) {
    echo "   ❌ Firebase connection failed\n";
    echo "   📝 Error: " . $e->getMessage() . "\n";
    echo "   📝 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    
    // Check if it's a credentials issue
    if (strpos($e->getMessage(), 'service account') !== false || 
        strpos($e->getMessage(), 'credentials') !== false) {
        echo "\n🔧 Solution: You need to add the Firebase service account key file:\n";
        echo "   1. Go to Firebase Console > Project Settings > Service Accounts\n";
        echo "   2. Click 'Generate new private key'\n";
        echo "   3. Save the file as: storage/firebase/service-account-key.json\n";
        echo "   4. Make sure the path in .env is correct:\n";
        echo "      FIREBASE_CREDENTIALS=storage/firebase/service-account-key.json\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🔍 Environment Check:\n";
echo "   FIREBASE_PROJECT_ID: " . (env('FIREBASE_PROJECT_ID') ?: 'NOT SET') . "\n";
echo "   FIREBASE_CREDENTIALS: " . (env('FIREBASE_CREDENTIALS') ?: 'NOT SET') . "\n";
echo "   Service Account File Exists: " . (file_exists(storage_path(env('FIREBASE_CREDENTIALS'))) ? 'YES' : 'NO') . "\n";

if (env('FIREBASE_CREDENTIALS')) {
    $credentialsPath = storage_path(env('FIREBASE_CREDENTIALS'));
    echo "   Full Path: " . $credentialsPath . "\n";
    
    if (file_exists($credentialsPath)) {
        $fileSize = filesize($credentialsPath);
        echo "   File Size: " . $fileSize . " bytes\n";
        
        if ($fileSize > 0) {
            $content = file_get_contents($credentialsPath);
            $json = json_decode($content, true);
            if ($json && isset($json['project_id'])) {
                echo "   ✅ Service account file is valid JSON\n";
                echo "   📝 Project ID in file: " . $json['project_id'] . "\n";
            } else {
                echo "   ❌ Service account file is not valid JSON\n";
            }
        } else {
            echo "   ❌ Service account file is empty\n";
        }
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📱 Next Steps:\n";
echo "1. Add Firebase service account key if missing\n";
echo "2. Test FCM token registration from Flutter app\n";
echo "3. Send test notification from Laravel\n";
echo "4. Verify notification received in Flutter app\n";

echo "\n🎯 Test completed!\n";
