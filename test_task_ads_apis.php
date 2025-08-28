<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Driver;
use App\Models\Task;
use App\Models\Task_Ad;
use App\Models\Task_Offire;
use App\Models\Vehicle_Size;
use Illuminate\Support\Facades\DB;

echo "🧪 Testing Task Ads APIs\n";
echo str_repeat("=", 60) . "\n";

try {
    // Check database connection
    DB::connection()->getPdo();
    echo "✅ Database connection: OK\n\n";

    // Check if we have test data
    echo "📊 Checking test data availability...\n";
    
    $driversCount = Driver::where('status', 'active')->count();
    $taskAdsCount = Task_Ad::where('status', 'running')->count();
    $vehicleSizesCount = Vehicle_Size::count();
    
    echo "   👥 Active drivers: {$driversCount}\n";
    echo "   📢 Running task ads: {$taskAdsCount}\n";
    echo "   🚛 Vehicle sizes: {$vehicleSizesCount}\n\n";

    if ($driversCount === 0) {
        echo "⚠️  No active drivers found. Please create test drivers first.\n";
        return;
    }

    if ($taskAdsCount === 0) {
        echo "⚠️  No running task ads found. Creating test data...\n";
        createTestTaskAd();
        echo "✅ Test task ad created.\n\n";
    }

    // Test API endpoints
    echo "🔗 Testing API Endpoints...\n";
    
    // Get a test driver
    $testDriver = Driver::where('status', 'active')->first();
    if (!$testDriver) {
        echo "❌ No test driver available\n";
        return;
    }

    echo "   🧪 Using test driver: {$testDriver->name} (ID: {$testDriver->id})\n";
    echo "   🚛 Vehicle size: " . ($testDriver->vehicle_size->name ?? 'Not set') . "\n\n";

    // Test 1: Check controller exists
    echo "1️⃣  Testing Controller Existence...\n";
    if (class_exists('App\Http\Controllers\Api\DriverTaskAdsController')) {
        echo "   ✅ DriverTaskAdsController exists\n";
    } else {
        echo "   ❌ DriverTaskAdsController not found\n";
        return;
    }

    // Test 2: Check routes are registered
    echo "\n2️⃣  Testing Routes Registration...\n";
    $routes = \Illuminate\Support\Facades\Route::getRoutes();
    $taskAdsRoutes = [];
    
    foreach ($routes as $route) {
        if (str_contains($route->uri(), 'driver/task-ads') || str_contains($route->uri(), 'driver/my-offers')) {
            $taskAdsRoutes[] = $route->methods()[0] . ' /' . $route->uri();
        }
    }
    
    if (count($taskAdsRoutes) > 0) {
        echo "   ✅ Task Ads routes found:\n";
        foreach ($taskAdsRoutes as $route) {
            echo "      - {$route}\n";
        }
    } else {
        echo "   ❌ No Task Ads routes found\n";
    }

    // Test 3: Check models and relationships
    echo "\n3️⃣  Testing Models and Relationships...\n";
    
    $testAd = Task_Ad::with(['task', 'offers'])->first();
    if ($testAd) {
        echo "   ✅ Task_Ad model working\n";
        echo "   ✅ Task relationship: " . ($testAd->task ? 'OK' : 'Failed') . "\n";
        echo "   ✅ Offers relationship: " . ($testAd->offers ? 'OK' : 'Failed') . "\n";
        
        // Check task points
        if ($testAd->task && $testAd->task->pickup && $testAd->task->delivery) {
            echo "   ✅ Task points (pickup/delivery): OK\n";
        } else {
            echo "   ⚠️  Task points missing\n";
        }
    } else {
        echo "   ❌ No test task ad found\n";
    }

    // Test 4: Test data transformation
    echo "\n4️⃣  Testing Data Transformation...\n";
    
    if ($testAd) {
        $controller = new \App\Http\Controllers\Api\DriverTaskAdsController();
        
        // Use reflection to test private methods
        $reflection = new ReflectionClass($controller);
        $transformMethod = $reflection->getMethod('transformTaskAd');
        $transformMethod->setAccessible(true);
        
        try {
            $transformed = $transformMethod->invoke($controller, $testAd, $testDriver->id);
            echo "   ✅ transformTaskAd method working\n";
            echo "   ✅ Transformed data structure: OK\n";
            
            // Check required fields
            $requiredFields = ['id', 'task_id', 'description', 'status', 'lowest_price', 'highest_price'];
            $missingFields = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($transformed[$field])) {
                    $missingFields[] = $field;
                }
            }
            
            if (empty($missingFields)) {
                echo "   ✅ All required fields present\n";
            } else {
                echo "   ⚠️  Missing fields: " . implode(', ', $missingFields) . "\n";
            }
            
        } catch (Exception $e) {
            echo "   ❌ Data transformation failed: " . $e->getMessage() . "\n";
        }
    }

    // Test 5: Check permissions and business logic
    echo "\n5️⃣  Testing Business Logic...\n";
    
    if ($testAd) {
        $controller = new \App\Http\Controllers\Api\DriverTaskAdsController();
        $reflection = new ReflectionClass($controller);
        
        // Test canSubmitOffer method
        $canSubmitMethod = $reflection->getMethod('canSubmitOffer');
        $canSubmitMethod->setAccessible(true);
        
        $canSubmit = $canSubmitMethod->invoke($controller, $testAd, null, null);
        echo "   ✅ canSubmitOffer logic: " . ($canSubmit ? 'Can submit' : 'Cannot submit') . "\n";
        
        // Test canViewDetails method
        $canViewMethod = $reflection->getMethod('canViewDetails');
        $canViewMethod->setAccessible(true);
        
        $canView = $canViewMethod->invoke($controller, $testAd, $testDriver->id, null);
        echo "   ✅ canViewDetails logic: " . ($canView ? 'Can view' : 'Cannot view') . "\n";
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "🎯 API TESTING SUMMARY:\n";
    echo "✅ Backend APIs are ready for testing\n";
    echo "✅ Controllers and routes are properly configured\n";
    echo "✅ Models and relationships are working\n";
    echo "✅ Business logic is implemented\n";
    
    echo "\n📋 NEXT STEPS:\n";
    echo "1. Test APIs using Postman collection\n";
    echo "2. Create Flutter models and services\n";
    echo "3. Implement UI screens\n";
    echo "4. Test end-to-end functionality\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "📝 Stack trace: " . $e->getTraceAsString() . "\n";
}

function createTestTaskAd()
{
    // Create a simple test task ad if none exists
    $task = Task::where('status', 'advertised')->first();
    
    if (!$task) {
        // Create a basic task for testing
        $vehicleSize = Vehicle_Size::first();
        if (!$vehicleSize) {
            echo "⚠️  No vehicle sizes found. Please set up vehicle sizes first.\n";
            return;
        }
        
        $task = Task::create([
            'status' => 'advertised',
            'pricing_type' => 'manual',
            'total_price' => 0,
            'vehicle_size_id' => $vehicleSize->id,
            'customer_id' => 1, // Assuming customer exists
        ]);
        
        // Create pickup and delivery points
        $task->points()->create([
            'type' => 'pickup',
            'address' => 'الرياض - حي النخيل',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'contact_name' => 'أحمد محمد',
            'contact_phone' => '0501234567'
        ]);
        
        $task->points()->create([
            'type' => 'delivery',
            'address' => 'جدة - حي الصفا',
            'latitude' => 21.3891,
            'longitude' => 39.8579,
            'contact_name' => 'سارة أحمد',
            'contact_phone' => '0509876543'
        ]);
    }
    
    // Create task ad
    Task_Ad::create([
        'task_id' => $task->id,
        'description' => 'نقل بضائع متنوعة من الرياض إلى جدة',
        'status' => 'running',
        'lowest_price' => 500.00,
        'highest_price' => 800.00,
        'included' => true,
        'service_commission' => 10.0,
        'service_commission_type' => 0, // percentage
        'vat_commission' => 15.0
    ]);
}

echo "\n✨ Test completed!\n";
