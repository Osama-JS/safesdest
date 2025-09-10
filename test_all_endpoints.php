<?php

function testEndpoint($url, $description) {
    echo "=== Testing: $description ===\n";
    echo "URL: $url\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    echo "HTTP Code: $httpCode\n";
    if ($error) {
        echo "cURL Error: $error\n";
    }
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data) {
            echo "Success: " . ($data['success'] ? 'true' : 'false') . "\n";
            if (isset($data['data'])) {
                if (is_array($data['data']) && isset($data['data']['data'])) {
                    echo "Items count: " . count($data['data']['data']) . "\n";
                } elseif (is_array($data['data'])) {
                    echo "Data keys: " . implode(', ', array_keys($data['data'])) . "\n";
                }
            }
            if (isset($data['message'])) {
                echo "Message: " . $data['message'] . "\n";
            }
        } else {
            echo "Response (first 200 chars): " . substr($response, 0, 200) . "\n";
        }
    } else {
        echo "No response received\n";
    }
    
    echo "\n";
}

$baseUrl = 'http://localhost/safedestssss/public/api/driver';

// Test endpoints
testEndpoint($baseUrl . '/task-ads/test-stats', 'Test Stats (No Auth)');
testEndpoint($baseUrl . '/health', 'Health Check');

echo "=== All Tests Complete ===\n";
