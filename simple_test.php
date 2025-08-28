<?php

echo "🔥 Firebase Integration Test\n";
echo str_repeat("=", 40) . "\n";

// Test 1: Check files
echo "1. Checking required files...\n";

$files = [
    'Flutter google-services.json' => 'safedests-app/android/app/google-services.json',
    'Firebase Service Class' => 'app/Services/FirebaseService.php',
    'Laravel .env file' => '.env'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "   ✅ {$name}: EXISTS\n";
    } else {
        echo "   ❌ {$name}: MISSING\n";
    }
}

echo "\n2. Checking .env configuration...\n";

$envContent = file_get_contents('.env');
$firebaseVars = [
    'FIREBASE_PROJECT_ID',
    'FIREBASE_CREDENTIALS',
    'FCM_SERVER_KEY'
];

foreach ($firebaseVars as $var) {
    if (strpos($envContent, $var) !== false) {
        echo "   ✅ {$var}: CONFIGURED\n";
    } else {
        echo "   ❌ {$var}: MISSING\n";
    }
}

echo "\n3. Checking Firebase credentials file...\n";
$credentialsPath = 'storage/firebase/service-account-key.json';
if (file_exists($credentialsPath)) {
    echo "   ✅ Service account key: EXISTS\n";
    $content = file_get_contents($credentialsPath);
    $json = json_decode($content, true);
    if ($json && isset($json['project_id'])) {
        echo "   ✅ Service account key: VALID JSON\n";
        echo "   📝 Project ID: " . $json['project_id'] . "\n";
    } else {
        echo "   ❌ Service account key: INVALID JSON\n";
    }
} else {
    echo "   ❌ Service account key: MISSING\n";
    echo "   📝 Expected location: {$credentialsPath}\n";
}

echo "\n4. Checking Flutter pubspec.yaml...\n";
$pubspecContent = file_get_contents('safedests-app/pubspec.yaml');
$firebasePackages = [
    'firebase_core',
    'firebase_messaging',
    'flutter_local_notifications'
];

foreach ($firebasePackages as $package) {
    if (strpos($pubspecContent, $package . ':') !== false && strpos($pubspecContent, '# ' . $package) === false) {
        echo "   ✅ {$package}: ENABLED\n";
    } else {
        echo "   ❌ {$package}: DISABLED OR MISSING\n";
    }
}

echo "\n" . str_repeat("=", 40) . "\n";
echo "📋 SUMMARY:\n";

$allGood = true;

// Check critical files
if (!file_exists('safedests-app/android/app/google-services.json')) {
    echo "❌ Missing google-services.json\n";
    $allGood = false;
}

if (!file_exists('storage/firebase/service-account-key.json')) {
    echo "❌ Missing Firebase service account key\n";
    $allGood = false;
}

if (!strpos($envContent, 'FIREBASE_PROJECT_ID')) {
    echo "❌ Missing Firebase configuration in .env\n";
    $allGood = false;
}

if ($allGood) {
    echo "✅ All critical components are in place!\n";
    echo "\n🚀 READY TO TEST:\n";
    echo "1. Run Flutter app: flutter run\n";
    echo "2. Login as a driver to register FCM token\n";
    echo "3. Test notifications from Laravel admin panel\n";
} else {
    echo "⚠️  Some components are missing.\n";
    echo "\n🔧 NEXT STEPS:\n";
    echo "1. Add missing Firebase service account key\n";
    echo "2. Update .env with Firebase configuration\n";
    echo "3. Ensure all packages are enabled\n";
}

echo "\n✨ Test completed!\n";
