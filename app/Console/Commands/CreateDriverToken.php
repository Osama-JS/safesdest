<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Driver;

class CreateDriverToken extends Command
{
    protected $signature = 'driver:create-token {driver_id?}';
    protected $description = 'Create authentication token for driver';

    public function handle()
    {
        $driverId = $this->argument('driver_id');
        
        if ($driverId) {
            $driver = Driver::find($driverId);
        } else {
            $driver = Driver::first();
        }

        if (!$driver) {
            $this->error('Driver not found!');
            return 1;
        }

        $this->info("Creating token for driver: {$driver->name} (ID: {$driver->id})");

        // Create token
        $token = $driver->createToken('test-token', ['driver'])->plainTextToken;

        $this->info("Token created successfully:");
        $this->line($token);

        // Test the token
        $this->info("\nTesting token...");
        
        $baseUrl = 'http://localhost/safedestssss/public/api/driver';
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ];

        // Test stats endpoint
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $baseUrl . '/task-ads/stats');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->info("Stats endpoint test - HTTP Code: $httpCode");
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['success']) && $data['success']) {
                $this->info("✅ Token works! Available ads: " . $data['data']['available_ads']);
            } else {
                $this->error("❌ Token test failed");
                $this->line("Response: " . substr($response, 0, 200));
            }
        }

        return 0;
    }
}
