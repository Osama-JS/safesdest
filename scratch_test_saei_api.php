<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$saeiKey = App\Models\Settings::getValue('saei_api_key');
$saeiBase = App\Models\Settings::getValue('saei_base_url', 'https://api.saei.automize.sa/api');
$phoneId = App\Models\Settings::getValue('saei_from_phone_id');

echo "Testing Saei API with key: " . substr($saeiKey, 0, 10) . "...\n";

// Test Saei me or ping
$endpoints = [
    '/v1/user',
    '/v1/me',
    '/v1/channels',
    '/v1/templates',
    '/v1/messages',
];

foreach ($endpoints as $ep) {
    try {
        $res = Illuminate\Support\Facades\Http::withoutVerifying()
            ->withHeaders([
                'Authorization' => "Bearer {$saeiKey}",
                'Accept' => 'application/json'
            ])
            ->timeout(5)
            ->get("{$saeiBase}{$ep}");
        echo "{$ep} => status: " . $res->status() . " | " . substr($res->body(), 0, 100) . "\n";
    } catch (\Exception $e) {
        echo "{$ep} => error: " . $e->getMessage() . "\n";
    }
}
