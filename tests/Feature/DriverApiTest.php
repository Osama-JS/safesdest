<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Driver;
use App\Models\Task;
use Laravel\Sanctum\Sanctum;

class DriverApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test driver
        $this->driver = Driver::factory()->create([
            'email' => 'test@driver.com',
            'password' => bcrypt('password123'),
            'status' => 'active',
            'online' => true,
            'free' => true
        ]);
    }

    /** @test */
    public function driver_can_login_successfully()
    {
        $response = $this->postJson('/api/driver/login', [
            'email' => 'test@driver.com',
            'password' => 'password123',
            'device_name' => 'Test Device',
            'device_id' => 'test-device-123',
            'fcm_token' => 'test-fcm-token'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'token',
                    'driver' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'status',
                        'online'
                    ],
                    'abilities'
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertNotEmpty($response->json('token'));
    }

    /** @test */
    public function driver_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/driver/login', [
            'email' => 'test@driver.com',
            'password' => 'wrongpassword',
            'device_name' => 'Test Device'
        ]);

        $response->assertStatus(401)
                ->assertJson([
                    'success' => false,
                    'message' => 'Invalid credentials'
                ]);
    }

    /** @test */
    public function authenticated_driver_can_get_profile()
    {
        Sanctum::actingAs($this->driver, ['driver:read']);

        $response = $this->getJson('/api/driver/profile');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'driver' => [
                        'id',
                        'name',
                        'email',
                        'phone',
                        'status',
                        'online',
                        'free'
                    ]
                ]);
    }

    /** @test */
    public function driver_can_update_location()
    {
        Sanctum::actingAs($this->driver, ['location:update']);

        $response = $this->postJson('/api/driver/location', [
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'accuracy' => 5.0,
            'speed' => 45.5,
            'heading' => 180.0
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Location updated successfully'
                ]);

        // Check if driver location was updated in database
        $this->driver->refresh();
        $this->assertEquals(24.7136, $this->driver->altitude); // altitude stores latitude
        $this->assertEquals(46.6753, $this->driver->longitude);
    }

    /** @test */
    public function driver_can_update_status()
    {
        Sanctum::actingAs($this->driver, ['driver:update']);

        $response = $this->postJson('/api/driver/status', [
            'online' => false,
            'free' => false,
            'status' => 'offline'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Status updated successfully'
                ]);

        // Check if driver status was updated
        $this->driver->refresh();
        $this->assertFalse($this->driver->online);
        $this->assertFalse($this->driver->free);
    }

    /** @test */
    public function driver_can_get_tasks()
    {
        Sanctum::actingAs($this->driver, ['tasks:read']);

        // Create some test tasks
        Task::factory()->count(3)->create([
            'driver_id' => $this->driver->id,
            'status' => 'accepted'
        ]);

        $response = $this->getJson('/api/driver/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'tasks',
                    'pagination' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total'
                    ]
                ]);
    }

    /** @test */
    public function driver_can_get_wallet_information()
    {
        Sanctum::actingAs($this->driver, ['wallet:read']);

        $response = $this->getJson('/api/driver/wallet');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'wallet' => [
                        'balance',
                        'debt_ceiling',
                        'pending_amount',
                        'total_earnings',
                        'currency'
                    ],
                    'commission' => [
                        'type',
                        'value'
                    ]
                ]);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected()
    {
        $response = $this->getJson('/api/driver/profile');
        $response->assertStatus(401);

        $response = $this->getJson('/api/driver/tasks');
        $response->assertStatus(401);

        $response = $this->postJson('/api/driver/location', [
            'latitude' => 24.7136,
            'longitude' => 46.6753
        ]);
        $response->assertStatus(401);
    }

    /** @test */
    public function driver_can_logout()
    {
        $token = $this->driver->createDriverToken('Test Device');
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token->plainTextToken,
        ])->postJson('/api/driver/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logout successful'
                ]);

        // Check if driver is offline after logout
        $this->driver->refresh();
        $this->assertFalse($this->driver->online);
    }
}
