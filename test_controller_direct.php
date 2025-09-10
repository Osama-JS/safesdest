<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\DriverTaskAdsController;
use App\Models\Driver;
use Illuminate\Support\Facades\Auth;

// Create Laravel app instance
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Testing Controller Methods Directly ===\n\n";

// Get first driver
$driver = Driver::first();
if (!$driver) {
    echo "No drivers found!\n";
    exit;
}

echo "Testing with driver: {$driver->name} (ID: {$driver->id}, Vehicle Size: {$driver->vehicle_size_id})\n\n";

// Create controller instance
$controller = new DriverTaskAdsController();

// Mock authentication - we'll manually pass driver to methods
// Auth::shouldReceive('user')->andReturn($driver);

try {
    // Test 1: Get Stats
    echo "1. Testing getStats():\n";
    $request = new Request();
    $response = $controller->getStats($request);
    $responseData = $response->getData(true);
    echo "Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
    echo "Data: " . json_encode($responseData['data'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 2: Get Task Ads
    echo "2. Testing index() - Task Ads List:\n";
    $request = new Request(['page' => 1, 'per_page' => 10]);
    $response = $controller->index($request);
    $responseData = $response->getData(true);
    echo "Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
    echo "Data count: " . count($responseData['data']['data']) . "\n";
    echo "Pagination: " . json_encode($responseData['data']['pagination'], JSON_PRETTY_PRINT) . "\n\n";

    // Test 3: Get My Offers
    echo "3. Testing myOffers():\n";
    $request = new Request(['page' => 1, 'per_page' => 10]);
    $response = $controller->myOffers($request);
    $responseData = $response->getData(true);
    echo "Success: " . ($responseData['success'] ? 'true' : 'false') . "\n";
    echo "Data count: " . count($responseData['data']['data']) . "\n";
    echo "Pagination: " . json_encode($responseData['data']['pagination'], JSON_PRETTY_PRINT) . "\n\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "=== Direct Controller Testing Complete ===\n";
