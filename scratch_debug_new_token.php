<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = App\Models\Settings::getValue('whatsapp_cloud_token');
$phoneId = App\Models\Settings::getValue('whatsapp_cloud_phone_id') ?: App\Models\Settings::getValue('saei_from_phone_id');
$wabaId = App\Models\Settings::getValue('whatsapp_cloud_waba_id');

echo "Phone ID: " . $phoneId . "\n";
echo "WABA ID: " . $wabaId . "\n";
echo "Token length: " . strlen($token) . "\n";
echo "Token starts with: " . substr($token, 0, 10) . "...\n";

// 1. Check /me (who is this token?)
$resMe = Illuminate\Support\Facades\Http::withToken($token)
    ->get("https://graph.facebook.com/v21.0/me");
echo "1. Me response: " . $resMe->body() . "\n";

// 2. Check phone ID with token
$resPhone = Illuminate\Support\Facades\Http::withToken($token)
    ->get("https://graph.facebook.com/v21.0/{$phoneId}");
echo "2. Phone ID response: " . $resPhone->body() . "\n";

// 3. Check WABA ID with token
if ($wabaId) {
    $resWaba = Illuminate\Support\Facades\Http::withToken($token)
        ->get("https://graph.facebook.com/v21.0/{$wabaId}?fields=id,name,phone_numbers");
    echo "3. WABA response: " . $resWaba->body() . "\n";
}

// 4. Check permissions of this token
$resPerms = Illuminate\Support\Facades\Http::withToken($token)
    ->get("https://graph.facebook.com/v21.0/me/permissions");
echo "4. Permissions: " . $resPerms->body() . "\n";
