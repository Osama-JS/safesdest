<?php

require_once 'vendor/autoload.php';

use App\Models\Driver;
use App\Models\Task;
use App\Models\Customer;
use App\Models\TaskPoint;

// Load Laravel application
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    // Find the test driver
    $driver = Driver::where('email', 'driver@test.com')->first();
    
    if (!$driver) {
        echo "❌ Driver not found. Please run create_wallet_data.php first.\n";
        exit;
    }
    
    echo "Driver found: " . $driver->name . " (ID: " . $driver->id . ")\n";
    
    // Create test customer if not exists
    $customer = Customer::where('email', 'customer@test.com')->first();
    if (!$customer) {
        $customer = new Customer();
        $customer->name = 'عميل تجريبي';
        $customer->email = 'customer@test.com';
        $customer->phone = '966501234567';
        $customer->password = bcrypt('password123');
        $customer->status = 'active';
        $customer->created_at = now();
        $customer->updated_at = now();
        $customer->save();
        
        echo "Test customer created: " . $customer->name . "\n";
    }
    
    // Clear existing tasks for this driver
    Task::where('driver_id', $driver->id)->delete();
    echo "Cleared existing tasks\n";
    
    // Create sample tasks
    $tasks = [
        [
            'status' => 'delivered',
            'total_price' => 150.00,
            'commission' => 22.50,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
            'notes' => 'تم التسليم بنجاح',
            'pickup_address' => 'الرياض - حي النخيل',
            'delivery_address' => 'الرياض - حي الملز',
            'completed_at' => now()->subDays(1),
        ],
        [
            'status' => 'delivered',
            'total_price' => 200.00,
            'commission' => 30.00,
            'payment_method' => 'card',
            'payment_status' => 'paid',
            'notes' => 'تسليم سريع',
            'pickup_address' => 'الرياض - حي العليا',
            'delivery_address' => 'الرياض - حي السليمانية',
            'completed_at' => now()->subDays(2),
        ],
        [
            'status' => 'accepted',
            'total_price' => 120.00,
            'commission' => 18.00,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'notes' => 'في انتظار الاستلام',
            'pickup_address' => 'الرياض - حي الورود',
            'delivery_address' => 'الرياض - حي الربوة',
            'accepted_at' => now()->subHours(2),
        ],
        [
            'status' => 'picked_up',
            'total_price' => 180.00,
            'commission' => 27.00,
            'payment_method' => 'card',
            'payment_status' => 'pending',
            'notes' => 'تم الاستلام - في الطريق للتسليم',
            'pickup_address' => 'الرياض - حي الشفا',
            'delivery_address' => 'الرياض - حي المروج',
            'accepted_at' => now()->subHours(4),
        ],
        [
            'status' => 'pending',
            'total_price' => 90.00,
            'commission' => 13.50,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
            'notes' => 'مهمة جديدة متاحة',
            'pickup_address' => 'الرياض - حي الياسمين',
            'delivery_address' => 'الرياض - حي الفيحاء',
        ],
    ];
    
    foreach ($tasks as $index => $taskData) {
        $task = new Task();
        $task->customer_id = $customer->id;
        
        if ($taskData['status'] === 'pending') {
            $task->pending_driver_id = $driver->id;
        } else {
            $task->driver_id = $driver->id;
        }
        
        $task->total_price = $taskData['total_price'];
        $task->commission = $taskData['commission'];
        $task->status = $taskData['status'];
        $task->payment_method = $taskData['payment_method'];
        $task->payment_status = $taskData['payment_status'];
        $task->notes = $taskData['notes'];
        $task->additional_data = [
            'items' => [
                ['name' => 'طرد صغير', 'quantity' => 1, 'weight' => '2kg'],
                ['name' => 'مستندات', 'quantity' => 1, 'weight' => '0.5kg']
            ],
            'special_instructions' => 'يرجى التعامل بحذر'
        ];
        
        $task->created_at = now()->subDays(rand(0, 7));
        $task->updated_at = now();
        
        if (isset($taskData['accepted_at'])) {
            $task->accepted_at = $taskData['accepted_at'];
        }
        
        if (isset($taskData['completed_at'])) {
            $task->completed_at = $taskData['completed_at'];
        }
        
        $task->save();
        
        // Create pickup point
        $pickupPoint = new TaskPoint();
        $pickupPoint->task_id = $task->id;
        $pickupPoint->type = 'pickup';
        $pickupPoint->address = $taskData['pickup_address'];
        $pickupPoint->latitude = 24.7136 + (rand(-100, 100) / 1000); // Random around Riyadh
        $pickupPoint->longitude = 46.6753 + (rand(-100, 100) / 1000);
        $pickupPoint->contact_name = 'نقطة الاستلام';
        $pickupPoint->contact_phone = '966501234567';
        $pickupPoint->save();
        
        // Create delivery point
        $deliveryPoint = new TaskPoint();
        $deliveryPoint->task_id = $task->id;
        $deliveryPoint->type = 'delivery';
        $deliveryPoint->address = $taskData['delivery_address'];
        $deliveryPoint->latitude = 24.7136 + (rand(-100, 100) / 1000); // Random around Riyadh
        $deliveryPoint->longitude = 46.6753 + (rand(-100, 100) / 1000);
        $deliveryPoint->contact_name = 'نقطة التسليم';
        $deliveryPoint->contact_phone = '966501234568';
        $deliveryPoint->save();
        
        echo "Created task #" . ($index + 1) . ": " . $taskData['status'] . " - " . $taskData['total_price'] . " SAR\n";
    }
    
    echo "\n=== Tasks Summary ===\n";
    echo "Driver: " . $driver->name . "\n";
    echo "Total Tasks: " . Task::where('driver_id', $driver->id)->orWhere('pending_driver_id', $driver->id)->count() . "\n";
    echo "Completed Tasks: " . Task::where('driver_id', $driver->id)->where('status', 'delivered')->count() . "\n";
    echo "Active Tasks: " . Task::where('driver_id', $driver->id)->whereIn('status', ['accepted', 'picked_up', 'in_transit'])->count() . "\n";
    echo "Pending Tasks: " . Task::where('pending_driver_id', $driver->id)->where('status', 'pending')->count() . "\n";
    echo "Total Earnings: " . Task::where('driver_id', $driver->id)->where('status', 'delivered')->sum('commission') . " SAR\n";
    echo "=====================\n";
    
    echo "\n✅ Test tasks created successfully!\n";
    echo "You can now test the tasks functionality in the app.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
