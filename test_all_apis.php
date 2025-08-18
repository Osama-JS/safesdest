<?php

require_once 'vendor/autoload.php';

use App\Models\Driver;
use Illuminate\Support\Facades\Hash;

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔍 Testing All APIs - SafeDests Driver App\n";
echo "==========================================\n\n";

try {
    // Find test driver
    $driver = Driver::where('email', 'driver@test.com')->first();
    
    if (!$driver) {
        echo "❌ Test driver not found. Please run create_wallet_data.php first.\n";
        exit;
    }
    
    echo "✅ Test driver found: " . $driver->name . " (ID: " . $driver->id . ")\n\n";
    
    // Create a token for testing
    $token = $driver->createToken('test-token')->plainTextToken;
    echo "🔑 Test token created: " . substr($token, 0, 20) . "...\n\n";
    
    // Base URL for testing
    $baseUrl = 'http://127.0.0.1:8000/api/driver';
    
    // Test endpoints
    $endpoints = [
        // Authentication
        [
            'name' => 'Login',
            'method' => 'POST',
            'url' => $baseUrl . '/login',
            'data' => [
                'email' => 'driver@test.com',
                'password' => 'password123',
                'device_name' => 'Test Device'
            ],
            'auth' => false
        ],
        
        // Profile
        [
            'name' => 'Get Profile',
            'method' => 'GET',
            'url' => $baseUrl . '/profile',
            'auth' => true
        ],
        [
            'name' => 'Get Profile Stats',
            'method' => 'GET',
            'url' => $baseUrl . '/profile/stats',
            'auth' => true
        ],
        
        // Tasks
        [
            'name' => 'Get Tasks',
            'method' => 'GET',
            'url' => $baseUrl . '/tasks',
            'auth' => true
        ],
        
        // Wallet
        [
            'name' => 'Get Wallet',
            'method' => 'GET',
            'url' => $baseUrl . '/wallet',
            'auth' => true
        ],
        [
            'name' => 'Get Transactions',
            'method' => 'GET',
            'url' => $baseUrl . '/wallet/transactions',
            'auth' => true
        ],
        [
            'name' => 'Get Earnings Stats',
            'method' => 'GET',
            'url' => $baseUrl . '/wallet/earnings/stats?period=month',
            'auth' => true
        ],
        
        // Location
        [
            'name' => 'Update Location',
            'method' => 'POST',
            'url' => $baseUrl . '/location',
            'data' => [
                'latitude' => 24.7136,
                'longitude' => 46.6753,
                'accuracy' => 10.0
            ],
            'auth' => true
        ],
        [
            'name' => 'Update Status',
            'method' => 'POST',
            'url' => $baseUrl . '/status',
            'data' => [
                'online' => true,
                'free' => true
            ],
            'auth' => true
        ],
        
        // Notifications
        [
            'name' => 'Get Notifications',
            'method' => 'GET',
            'url' => $baseUrl . '/notifications',
            'auth' => true
        ]
    ];
    
    $successCount = 0;
    $totalCount = count($endpoints);
    
    foreach ($endpoints as $endpoint) {
        echo "🧪 Testing: " . $endpoint['name'] . "\n";
        echo "   Method: " . $endpoint['method'] . "\n";
        echo "   URL: " . $endpoint['url'] . "\n";
        
        // Initialize cURL
        $ch = curl_init();
        
        // Set basic options
        curl_setopt($ch, CURLOPT_URL, $endpoint['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        // Set headers
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        
        // Add authorization if needed
        if ($endpoint['auth']) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        // Set method and data
        if ($endpoint['method'] === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (isset($endpoint['data'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($endpoint['data']));
            }
        } elseif ($endpoint['method'] === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if (isset($endpoint['data'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($endpoint['data']));
            }
        }
        
        // Execute request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Check results
        if ($error) {
            echo "   ❌ cURL Error: " . $error . "\n";
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                echo "   ✅ Success (HTTP " . $httpCode . ")\n";
                
                // Show data structure for important endpoints
                if (in_array($endpoint['name'], ['Get Tasks', 'Get Wallet', 'Get Transactions'])) {
                    if (isset($data['data'])) {
                        echo "   📊 Data structure: " . json_encode(array_keys($data['data'])) . "\n";
                    }
                }
                
                $successCount++;
            } else {
                echo "   ⚠️  Response Success: false (HTTP " . $httpCode . ")\n";
                if ($data && isset($data['message'])) {
                    echo "   📝 Message: " . $data['message'] . "\n";
                }
            }
        } else {
            echo "   ❌ HTTP Error: " . $httpCode . "\n";
            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['message'])) {
                    echo "   📝 Error: " . $data['message'] . "\n";
                }
            }
        }
        
        echo "\n";
        
        // Small delay between requests
        usleep(500000); // 0.5 seconds
    }
    
    // Summary
    echo "📊 API Testing Summary\n";
    echo "=====================\n";
    echo "Total Endpoints: " . $totalCount . "\n";
    echo "Successful: " . $successCount . "\n";
    echo "Failed: " . ($totalCount - $successCount) . "\n";
    echo "Success Rate: " . round(($successCount / $totalCount) * 100, 2) . "%\n\n";
    
    if ($successCount === $totalCount) {
        echo "🎉 All APIs are working perfectly!\n";
    } elseif ($successCount > ($totalCount * 0.8)) {
        echo "✅ Most APIs are working well!\n";
    } else {
        echo "⚠️  Some APIs need attention.\n";
    }
    
    // Clean up test token
    $driver->tokens()->where('name', 'test-token')->delete();
    echo "🧹 Test token cleaned up.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
