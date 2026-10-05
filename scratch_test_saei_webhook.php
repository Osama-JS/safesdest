<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$payload = json_decode('{"id":"evt_01M44NQN4GA57T9KK64GH4W0MT","version":"1","type":"message.received","occurred_at":"2026-10-04T23:59:35+00:00","workspace_id":"ws_37","data":{"messaging_product":"whatsapp","metadata":{"display_phone_number":"966557507505","phone_number_id":"1276243858896899"},"contacts":[{"profile":{"name":"Osama"},"wa_id":"967777958051","user_id":"YE.2389974241497000"}],"messages":[{"from":"967777958051","from_user_id":"YE.2389974241497000","id":"wamid.HBgMOTY3Nzc3OTU4MDUxFQIAEhggQUMyNkEzODRFMUI2M0U0M0MyRDc2QzRBRkExMjkzMzIA","timestamp":"1791158373","text":{"body":"مرحبا"},"type":"text"}]},"meta_raw":{"value":{"messaging_product":"whatsapp","metadata":{"display_phone_number":"966557507505","phone_number_id":"1276243858896899"},"contacts":[{"profile":{"name":"Osama"},"wa_id":"967777958051","user_id":"YE.2389974241497000"}],"messages":[{"from":"967777958051","from_user_id":"YE.2389974241497000","id":"wamid.HBgMOTY3Nzc3OTU4MDUxFQIAEhggQUMyNkEzODRFMUI2M0U0M0MyRDc2QzRBRkExMjkzMzIA","timestamp":"1791158373","text":{"body":"مرحبا"},"type":"text"}]},"field":"messages"}}', true);

$request = Illuminate\Http\Request::create('/api/whatsapp/webhook', 'POST', $payload);
$response = app(App\Http\Controllers\Api\WhatsAppWebhookController::class)->handle($request);

echo "Status: " . $response->getStatusCode() . "\n";
echo "Messages count: " . App\Models\WhatsappMessage::count() . "\n";
$lastMsg = App\Models\WhatsappMessage::latest()->first();
if ($lastMsg) {
    echo "Last Message: " . $lastMsg->content . " | direction: " . $lastMsg->direction . "\n";
    echo "Conversation ID: " . $lastMsg->conversation_id . "\n";
    $conv = $lastMsg->conversation;
    echo "Conversation Phone: " . $conv->phone_number . " | Unread: " . $conv->unread_count . "\n";
}
