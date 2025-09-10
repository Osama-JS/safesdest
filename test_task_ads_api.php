<?php

require_once 'bootstrap/app.php';

use App\Models\Driver;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\DriverTaskAdsController;

echo "=== Testing Task Ads API Fixes ===\n\n";

// Create a test driver
$driver = Driver::first();
if (!$driver) {
    echo "❌ No drivers found in database\n";
    exit(1);
}

echo "✅ Using driver: {$driver->name} (ID: {$driver->id})\n\n";

// Create controller instance
$controller = new DriverTaskAdsController();

// Test 1: Get Task Ads Statistics
echo "📊 Test 1: Get Task Ads Statistics\n";
echo "-----------------------------------\n";

// Mock authentication
auth()->guard('driver')->login($driver);

$request = new Request();
$response = $controller->getStats($request);
$responseData = json_decode($response->getContent(), true);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Data: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";

if ($responseData['success']) {
    echo "✅ Statistics retrieved successfully\n";
    echo "Available Ads: " . $responseData['data']['available_ads'] . "\n";
    echo "My Offers: " . $responseData['data']['my_offers'] . "\n";
    echo "Accepted Offers: " . $responseData['data']['accepted_offers'] . "\n";
} else {
    echo "❌ Failed to get statistics: " . $responseData['message'] . "\n";
}

echo "\n";

// Test 2: Get Task Ads List (with fixed parsing)
echo "📋 Test 2: Get Task Ads List\n";
echo "----------------------------\n";

$request = new Request([
    'page' => 1,
    'per_page' => 5,
    'sort_by' => 'created_at',
    'sort_order' => 'desc'
]);

$response = $controller->index($request);
$responseData = json_decode($response->getContent(), true);

echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Structure: " . json_encode(array_keys($responseData), JSON_PRETTY_PRINT) . "\n";

if (isset($responseData['data']['data'])) {
    echo "Ads Count: " . count($responseData['data']['data']) . "\n";
    echo "Pagination: " . json_encode($responseData['data']['pagination'], JSON_PRETTY_PRINT) . "\n";
} else {
    echo "❌ Invalid response structure\n";
}

echo "\n";

// Test 3: Database Statistics
echo "🗄️  Test 3: Database Statistics\n";
echo "-------------------------------\n";

$totalAds = Task_Ad::count();
$runningAds = Task_Ad::where('status', 'running')->count();
$closedAds = Task_Ad::where('status', 'closed')->count();
$totalOffers = Task_Offire::count();
$acceptedOffers = Task_Offire::where('accepted', true)->count();
$driverOffers = Task_Offire::where('driver_id', $driver->id)->count();

echo "Total Ads: $totalAds\n";
echo "Running Ads: $runningAds\n";
echo "Closed Ads: $closedAds\n";
echo "Total Offers: $totalOffers\n";
echo "Accepted Offers: $acceptedOffers\n";
echo "Driver's Offers: $driverOffers\n";

echo "\n=== Test Complete ===\n";
