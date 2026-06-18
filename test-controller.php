<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$req = \Illuminate\Http\Request::create('/admin/reports/customer-tasks/preview', 'POST', [
    'date_from' => '2020-01-01', 
    'date_to' => '2030-01-01', 
    'columns' => ['driver_extra:1']
]);
$c = app()->make(\App\Http\Controllers\admin\PlatformReportsController::class);
$res = $c->getReportPreview($req)->getContent();
$data = json_decode($res, true);

if (isset($data['data']) && count($data['data']) > 0) {
    echo "First task:\n";
    echo json_encode(array_filter($data['data'][0], function($k) { return str_starts_with($k, 'driver_extra:'); }, ARRAY_FILTER_USE_KEY), JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "No tasks or data missing.\n";
    echo $res;
}
