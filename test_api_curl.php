<?php

$url = 'http://localhost/safedestssss/public/api/driver/task-ads/test-stats';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "Testing URL: $url\n\n";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "cURL Error: " . ($error ?: 'None') . "\n";
echo "Response: $response\n";

if ($response) {
    $data = json_decode($response, true);
    if ($data) {
        echo "\nParsed Response:\n";
        print_r($data);
    }
}
