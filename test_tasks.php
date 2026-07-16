<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// We need a specific user id to test, let's just get all tasks that have a broker_id
$tasks = \App\Models\Task::whereNotNull('broker_id')->select('id', 'status', 'closed', 'broker_commission_type', 'broker_commission_value', 'broker_id')->take(10)->get();
echo json_encode($tasks->toArray(), JSON_PRETTY_PRINT);
