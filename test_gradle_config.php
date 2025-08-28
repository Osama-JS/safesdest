<?php

echo "🔧 Gradle Configuration Test\n";
echo str_repeat("=", 50) . "\n";

$androidDir = 'safedests-app/android';
$appDir = $androidDir . '/app';

echo "1. Checking Gradle files...\n";

// Check main build.gradle
$mainBuildGradle = $androidDir . '/build.gradle';
if (file_exists($mainBuildGradle)) {
    echo "   ✅ Main build.gradle: EXISTS\n";
    
    $content = file_get_contents($mainBuildGradle);
    
    // Check for buildscript repositories
    if (strpos($content, 'buildscript') !== false && strpos($content, 'repositories') !== false) {
        echo "   ✅ buildscript repositories: CONFIGURED\n";
    } else {
        echo "   ❌ buildscript repositories: MISSING\n";
    }
    
    // Check for Google Services classpath
    if (strpos($content, 'com.google.gms:google-services') !== false) {
        echo "   ✅ Google Services classpath: CONFIGURED\n";
    } else {
        echo "   ❌ Google Services classpath: MISSING\n";
    }
    
} else {
    echo "   ❌ Main build.gradle: MISSING\n";
}

// Check app build.gradle
$appBuildGradle = $appDir . '/build.gradle';
if (file_exists($appBuildGradle)) {
    echo "   ✅ App build.gradle: EXISTS\n";
    
    $content = file_get_contents($appBuildGradle);
    
    // Check for Google Services plugin
    if (strpos($content, 'com.google.gms.google-services') !== false) {
        echo "   ✅ Google Services plugin: APPLIED\n";
    } else {
        echo "   ❌ Google Services plugin: NOT APPLIED\n";
    }
    
    // Check for Firebase dependencies
    if (strpos($content, 'firebase-bom') !== false) {
        echo "   ✅ Firebase BOM: CONFIGURED\n";
    } else {
        echo "   ❌ Firebase BOM: MISSING\n";
    }
    
} else {
    echo "   ❌ App build.gradle: MISSING\n";
}

echo "\n2. Checking google-services.json...\n";

$googleServicesJson = $appDir . '/google-services.json';
if (file_exists($googleServicesJson)) {
    echo "   ✅ google-services.json: EXISTS\n";
    
    $content = file_get_contents($googleServicesJson);
    $json = json_decode($content, true);
    
    if ($json && isset($json['project_info']['project_id'])) {
        echo "   ✅ JSON format: VALID\n";
        echo "   📝 Project ID: " . $json['project_info']['project_id'] . "\n";
        
        if (isset($json['client'][0]['client_info']['android_client_info']['package_name'])) {
            $packageName = $json['client'][0]['client_info']['android_client_info']['package_name'];
            echo "   📝 Package Name: " . $packageName . "\n";
            
            // Check if package name matches build.gradle
            $appBuildContent = file_get_contents($appBuildGradle);
            if (strpos($appBuildContent, $packageName) !== false) {
                echo "   ✅ Package name matches build.gradle\n";
            } else {
                echo "   ❌ Package name mismatch with build.gradle\n";
            }
        }
    } else {
        echo "   ❌ JSON format: INVALID\n";
    }
    
} else {
    echo "   ❌ google-services.json: MISSING\n";
}

echo "\n3. Checking AndroidManifest.xml...\n";

$manifestPath = $appDir . '/src/main/AndroidManifest.xml';
if (file_exists($manifestPath)) {
    echo "   ✅ AndroidManifest.xml: EXISTS\n";
    
    $content = file_get_contents($manifestPath);
    
    // Check for Firebase permissions
    $permissions = [
        'android.permission.INTERNET',
        'android.permission.WAKE_LOCK',
        'android.permission.VIBRATE'
    ];
    
    foreach ($permissions as $permission) {
        if (strpos($content, $permission) !== false) {
            echo "   ✅ Permission {$permission}: ADDED\n";
        } else {
            echo "   ❌ Permission {$permission}: MISSING\n";
        }
    }
    
    // Check for Firebase metadata
    if (strpos($content, 'com.google.firebase.messaging.default_notification_channel_id') !== false) {
        echo "   ✅ Firebase metadata: CONFIGURED\n";
    } else {
        echo "   ❌ Firebase metadata: MISSING\n";
    }
    
} else {
    echo "   ❌ AndroidManifest.xml: MISSING\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "📋 CONFIGURATION SUMMARY:\n";

$issues = [];

// Check main build.gradle
if (!file_exists($mainBuildGradle)) {
    $issues[] = "Main build.gradle missing";
} else {
    $content = file_get_contents($mainBuildGradle);
    if (strpos($content, 'buildscript') === false || strpos($content, 'repositories') === false) {
        $issues[] = "buildscript repositories not configured";
    }
    if (strpos($content, 'com.google.gms:google-services') === false) {
        $issues[] = "Google Services classpath missing";
    }
}

// Check app build.gradle
if (!file_exists($appBuildGradle)) {
    $issues[] = "App build.gradle missing";
} else {
    $content = file_get_contents($appBuildGradle);
    if (strpos($content, 'com.google.gms.google-services') === false) {
        $issues[] = "Google Services plugin not applied";
    }
}

// Check google-services.json
if (!file_exists($googleServicesJson)) {
    $issues[] = "google-services.json missing";
}

if (empty($issues)) {
    echo "✅ All configurations are correct!\n";
    echo "\n🚀 You can now try:\n";
    echo "   flutter clean\n";
    echo "   flutter pub get\n";
    echo "   flutter build apk --debug\n";
} else {
    echo "❌ Found issues:\n";
    foreach ($issues as $issue) {
        echo "   - {$issue}\n";
    }
}

echo "\n✨ Configuration test completed!\n";
