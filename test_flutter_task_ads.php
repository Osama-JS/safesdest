<?php

require_once 'bootstrap/app.php';

use App\Models\Driver;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\DriverTaskAdsController;

echo "=== Testing Flutter Task Ads APIs ===\n\n";

// Create a test driver
$driver = Driver::first();
if (!$driver) {
    echo "❌ No drivers found in database\n";
    exit(1);
}

echo "✅ Using driver: {$driver->name} (ID: {$driver->id})\n\n";

// Create controller instance
$controller = new DriverTaskAdsController();

// Test 1: Get Task Ads List
echo "📋 Test 1: Get Task Ads List\n";
echo "----------------------------\n";

$request = new Request([
    'page' => 1,
    'per_page' => 5,
    'sort_by' => 'created_at',
    'sort_order' => 'desc'
]);

// Mock authentication
$request->setUserResolver(function () use ($driver) {
    return $driver;
});

try {
    $response = $controller->index($request);
    $data = $response->getData(true);
    
    if ($data['success']) {
        echo "✅ Successfully retrieved task ads\n";
        echo "   Total ads: " . count($data['data']['data']) . "\n";
        echo "   Current page: " . $data['data']['current_page'] . "\n";
        echo "   Total pages: " . $data['data']['last_page'] . "\n";
        
        if (!empty($data['data']['data'])) {
            $firstAd = $data['data']['data'][0];
            echo "   First ad ID: " . $firstAd['id'] . "\n";
            echo "   Price range: " . $firstAd['lowest_price'] . " - " . $firstAd['highest_price'] . " SAR\n";
        }
    } else {
        echo "❌ Failed to retrieve task ads: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Get Specific Task Ad Details
echo "🔍 Test 2: Get Task Ad Details\n";
echo "------------------------------\n";

$taskAd = Task_Ad::where('status', 'running')->first();
if ($taskAd) {
    try {
        $response = $controller->show($request, $taskAd->id);
        $data = $response->getData(true);
        
        if ($data['success']) {
            echo "✅ Successfully retrieved task ad details\n";
            echo "   Ad ID: " . $data['data']['id'] . "\n";
            echo "   Status: " . $data['data']['status'] . "\n";
            echo "   Can submit offer: " . ($data['data']['can_submit_offer'] ? 'Yes' : 'No') . "\n";
            echo "   Can view details: " . ($data['data']['can_view_details'] ? 'Yes' : 'No') . "\n";
            echo "   Offers count: " . $data['data']['offers_count'] . "\n";
        } else {
            echo "❌ Failed to retrieve task ad details: " . $data['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No running task ads found for testing\n";
}

echo "\n";

// Test 3: Submit Offer
echo "💰 Test 3: Submit Offer\n";
echo "-----------------------\n";

if ($taskAd) {
    // Check if driver already has an offer for this ad
    $existingOffer = Task_Offire::where('task_ad_id', $taskAd->id)
                                ->where('driver_id', $driver->id)
                                ->first();
    
    if (!$existingOffer) {
        $offerRequest = new Request([
            'price' => $taskAd->lowest_price + 10,
            'description' => 'Test offer from Flutter API test'
        ]);
        
        $offerRequest->setUserResolver(function () use ($driver) {
            return $driver;
        });
        
        try {
            $response = $controller->submitOffer($offerRequest, $taskAd->id);
            $data = $response->getData(true);
            
            if ($data['success']) {
                echo "✅ Successfully submitted offer\n";
                echo "   Offer ID: " . $data['data']['id'] . "\n";
                echo "   Price: " . $data['data']['price'] . " SAR\n";
                echo "   Status: " . $data['data']['status'] . "\n";
                
                $offerId = $data['data']['id'];
            } else {
                echo "❌ Failed to submit offer: " . $data['message'] . "\n";
            }
        } catch (Exception $e) {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️  Driver already has an offer for this ad (ID: {$existingOffer->id})\n";
        $offerId = $existingOffer->id;
    }
} else {
    echo "⚠️  No task ad available for testing\n";
}

echo "\n";

// Test 4: Update Offer
echo "✏️  Test 4: Update Offer\n";
echo "-----------------------\n";

if (isset($offerId)) {
    $updateRequest = new Request([
        'price' => $taskAd->lowest_price + 15,
        'description' => 'Updated test offer from Flutter API test'
    ]);
    
    $updateRequest->setUserResolver(function () use ($driver) {
        return $driver;
    });
    
    try {
        $response = $controller->updateOffer($updateRequest, $offerId);
        $data = $response->getData(true);
        
        if ($data['success']) {
            echo "✅ Successfully updated offer\n";
            echo "   Offer ID: " . $data['data']['id'] . "\n";
            echo "   New price: " . $data['data']['price'] . " SAR\n";
            echo "   Status: " . $data['data']['status'] . "\n";
        } else {
            echo "❌ Failed to update offer: " . $data['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No offer available for testing\n";
}

echo "\n";

// Test 5: Get Ad Offers
echo "📊 Test 5: Get Ad Offers\n";
echo "------------------------\n";

if ($taskAd) {
    try {
        $response = $controller->getAdOffers($request, $taskAd->id);
        $data = $response->getData(true);
        
        if ($data['success']) {
            echo "✅ Successfully retrieved ad offers\n";
            echo "   Total offers: " . count($data['data']['offers']) . "\n";
            
            foreach ($data['data']['offers'] as $offer) {
                echo "   - Offer ID: {$offer['id']}, Price: {$offer['price']} SAR, Status: {$offer['status']}\n";
            }
        } else {
            echo "❌ Failed to retrieve ad offers: " . $data['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️  No task ad available for testing\n";
}

echo "\n";

// Test 6: Search and Filter
echo "🔎 Test 6: Search and Filter\n";
echo "----------------------------\n";

$searchRequest = new Request([
    'search' => 'delivery',
    'min_price' => 50,
    'max_price' => 200,
    'sort_by' => 'lowest_price',
    'sort_order' => 'asc',
    'page' => 1,
    'per_page' => 10
]);

$searchRequest->setUserResolver(function () use ($driver) {
    return $driver;
});

try {
    $response = $controller->index($searchRequest);
    $data = $response->getData(true);
    
    if ($data['success']) {
        echo "✅ Successfully performed search and filter\n";
        echo "   Results found: " . count($data['data']['data']) . "\n";
        echo "   Search term: 'delivery'\n";
        echo "   Price range: 50 - 200 SAR\n";
        echo "   Sort by: lowest_price (asc)\n";
    } else {
        echo "❌ Failed to perform search: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Summary ===\n";
echo "All Flutter Task Ads API tests completed!\n";
echo "Check the results above for any issues.\n\n";

// Display some statistics
$totalAds = Task_Ad::count();
$runningAds = Task_Ad::where('status', 'running')->count();
$totalOffers = Task_Offire::count();
$driverOffers = Task_Offire::where('driver_id', $driver->id)->count();

echo "📈 Database Statistics:\n";
echo "   Total task ads: {$totalAds}\n";
echo "   Running ads: {$runningAds}\n";
echo "   Total offers: {$totalOffers}\n";
echo "   Driver's offers: {$driverOffers}\n";

?>
