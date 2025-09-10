<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Models\Driver;

// Create Laravel app instance
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Creating Driver Token ===\n\n";

// Get first driver
$driver = Driver::first();
if (!$driver) {
    echo "No drivers found!\n";
    exit;
}

echo "Driver: {$driver->name} (ID: {$driver->id})\n";

// Create token
$token = $driver->createToken('test-token', ['driver'])->plainTextToken;

echo "Token created: $token\n\n";

// Test the token
$baseUrl = 'http://localhost/safedestssss/public/api/driver';
$headers = [
    'Accept: application/json',
    'Content-Type: application/json',
    'Authorization: Bearer ' . $token
];

echo "Testing authenticated endpoints:\n\n";

// Test stats endpoint
echo "1. Testing /task-ads/stats:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/task-ads/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['data'])) {
            echo "Available Ads: " . $data['data']['available_ads'] . "\n";
            echo "My Offers: " . $data['data']['my_offers'] . "\n";
            echo "Accepted Offers: " . $data['data']['accepted_offers'] . "\n";
        }
    } else {
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n";

// Test task ads list
echo "2. Testing /task-ads:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/task-ads?page=1&per_page=5');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['success'])) {
        echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
        if (isset($data['data']['data'])) {
            echo "Task Ads Count: " . count($data['data']['data']) . "\n";
            echo "Total: " . $data['data']['pagination']['total'] . "\n";
        }
    } else {
        echo "Response: " . substr($response, 0, 200) . "\n";
    }
}

echo "\n=== Token Testing Complete ===\n";
