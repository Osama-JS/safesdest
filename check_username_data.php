<?php

require_once 'vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

// إعداد قاعدة البيانات
$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'localhost',
    'database' => 'safedest',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
]);

$capsule->setAsGlobal();
$capsule->bootEloquent();

try {
    // جلب جميع السائقين مع username
    $drivers = Capsule::table('drivers')
        ->select('id', 'name', 'email', 'username')
        ->get();

    echo "=== فحص بيانات username للسائقين ===\n\n";
    
    foreach ($drivers as $driver) {
        echo "Driver ID: {$driver->id}\n";
        echo "Name: {$driver->name}\n";
        echo "Email: {$driver->email}\n";
        echo "Username: " . ($driver->username ?? 'NULL') . "\n";
        echo "Username is null: " . (is_null($driver->username) ? 'YES' : 'NO') . "\n";
        echo "Username is empty: " . (empty($driver->username) ? 'YES' : 'NO') . "\n";
        echo "---\n";
    }
    
    // إحصائيات
    $totalDrivers = $drivers->count();
    $driversWithUsername = $drivers->filter(function($driver) {
        return !is_null($driver->username) && !empty($driver->username);
    })->count();
    $driversWithoutUsername = $totalDrivers - $driversWithUsername;
    
    echo "\n=== الإحصائيات ===\n";
    echo "إجمالي السائقين: {$totalDrivers}\n";
    echo "السائقين مع username: {$driversWithUsername}\n";
    echo "السائقين بدون username: {$driversWithoutUsername}\n";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
