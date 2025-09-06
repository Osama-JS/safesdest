<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Customer;
use App\Models\Task;
use App\Models\Wallet;
use App\Models\Payment;
use App\Models\Customs_Clearance;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;

class CustomerApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test customer
        $this->customer = Customer::factory()->create([
            'email' => 'test@example.com',
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
    }

    /** @test */
    public function customer_can_login()
    {
        $response = $this->postJson('/api/customer/auth/login', [
            'email' => $this->customer->email,
            'password' => 'password',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'customer',
                        'token',
                        'token_type',
                        'expires_at',
                    ]
                ]);
    }

    /** @test */
    public function customer_can_register()
    {
        $response = $this->postJson('/api/customer/auth/register', [
            'name' => 'Test Customer',
            'email' => 'newcustomer@example.com',
            'phone' => '+966501234567',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'device_name' => 'Test Device',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'customer',
                        'token',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_view_profile()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/customer/profile');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'customer' => [
                            'id',
                            'name',
                            'email',
                            'phone',
                        ],
                        'dynamic_fields',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_view_dashboard()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/customer/dashboard');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'overview',
                        'recent_activities',
                        'notifications_count',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_create_task()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/tasks', [
            'task_type' => 'normal',
            'vehicle_type' => 'small_car',
            'from_address' => 'Riyadh, Saudi Arabia',
            'to_address' => 'Jeddah, Saudi Arabia',
            'from_lat' => 24.7136,
            'from_lng' => 46.6753,
            'to_lat' => 21.4858,
            'to_lng' => 39.1925,
            'pickup_time' => now()->addHours(2)->toISOString(),
            'delivery_time' => now()->addHours(8)->toISOString(),
            'description' => 'Test task description',
            'assign_type' => 'advertised',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'task' => [
                            'id',
                            'task_type',
                            'status',
                            'from_address',
                            'to_address',
                        ]
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_view_tasks()
    {
        Sanctum::actingAs($this->customer);

        // Create some test tasks
        Task::factory()->count(3)->create(['customer_id' => $this->customer->id]);

        $response = $this->getJson('/api/customer/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'tasks',
                        'pagination',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_view_wallet()
    {
        Sanctum::actingAs($this->customer);

        // Create wallet for customer
        Wallet::factory()->create([
            'user_type' => 'customer',
            'user_id' => $this->customer->id,
            'balance' => 1000.00,
        ]);

        $response = $this->getJson('/api/customer/wallet');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'wallet' => [
                            'balance',
                            'currency',
                        ],
                        'recent_transactions',
                        'statistics',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_get_payment_methods()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/customer/payments/methods');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'payment_methods',
                        'wallet_balance',
                        'currency',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_create_customs_clearance()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/customs-clearances', [
            'clearance_type' => 'import',
            'goods_description' => 'Electronic devices',
            'goods_value' => 5000.00,
            'goods_weight' => 50.5,
            'origin_country' => 'China',
            'destination_port' => 'Jeddah Port',
            'expected_arrival' => now()->addDays(7)->format('Y-m-d'),
            'assign_type' => 'advertised',
            'notes' => 'Handle with care',
        ]);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'data' => [
                        'clearance' => [
                            'id',
                            'clearance_type',
                            'status',
                            'goods_description',
                        ]
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_view_notifications()
    {
        Sanctum::actingAs($this->customer);

        // Create some test notifications
        Notification::factory()->count(5)->create([
            'user_type' => 'customer',
            'user_id' => $this->customer->id,
        ]);

        $response = $this->getJson('/api/customer/notifications');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'notifications',
                        'unread_count',
                        'pagination',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_get_settings()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->getJson('/api/customer/settings');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'customer_settings',
                        'app_config',
                        'business_settings',
                        'features',
                    ]
                ]);
    }

    /** @test */
    public function authenticated_customer_can_get_task_ads()
    {
        Sanctum::actingAs($this->customer);

        // Create some advertised tasks
        Task::factory()->count(3)->create(['status' => 'advertised']);

        $response = $this->getJson('/api/customer/ads/tasks');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'ads',
                        'pagination',
                    ]
                ]);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected()
    {
        $response = $this->getJson('/api/customer/profile');
        $response->assertStatus(401);

        $response = $this->getJson('/api/customer/tasks');
        $response->assertStatus(401);

        $response = $this->getJson('/api/customer/wallet');
        $response->assertStatus(401);
    }

    /** @test */
    public function invalid_data_returns_validation_errors()
    {
        Sanctum::actingAs($this->customer);

        // Test invalid task creation
        $response = $this->postJson('/api/customer/tasks', [
            'task_type' => 'invalid_type',
            'from_address' => '', // Required field
        ]);

        $response->assertStatus(422)
                ->assertJsonStructure([
                    'success',
                    'message',
                    'errors',
                ]);
    }

    /** @test */
    public function customer_can_logout()
    {
        Sanctum::actingAs($this->customer);

        $response = $this->postJson('/api/customer/auth/logout');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Logged out successfully',
                ]);
    }
}
