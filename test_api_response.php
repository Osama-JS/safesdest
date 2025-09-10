<?php

// Test the API endpoints directly
$baseUrl = 'http://localhost/safedestssss/public/api/driver';

// Test with a sample driver token (you'll need to get a real token)
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer 1|your-token-here' // Replace with real token
];

echo "=== Testing Task Ads API ===\n\n";

// Test 1: Get task ads list
echo "1. Testing /task-ads endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/task-ads');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 2: Get stats
echo "2. Testing /task-ads/stats endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/task-ads/stats');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

// Test 3: Get my offers
echo "3. Testing /my-offers endpoint:\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/my-offers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n\n";

echo "=== API Testing Complete ===\n";
