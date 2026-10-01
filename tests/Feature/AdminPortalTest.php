<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Database\Seeders\VehicleStatusTableSeeder;
use Database\Seeders\VehicleTypeTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
        $this->seed(VehicleTypeTableSeeder::class);
        $this->seed(VehicleStatusTableSeeder::class);
    }

    public function test_admin_can_read_dashboard_stats_and_reports(): void
    {
        $admin = $this->makeUser(1);
        $customer = $this->makeUser(3);
        $this->makeOrder($customer);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard/stats')
            ->assertOk()
            ->assertJsonPath('data.total_orders', 1)
            ->assertJsonStructure(['data' => [
                'unassigned_orders',
                'online_drivers',
                'pending_payments',
                'delivered_today',
            ]]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard/reports')
            ->assertOk()
            ->assertJsonStructure(['data' => ['series', 'by_type', 'by_shop', 'drivers']]);
    }

    public function test_admin_can_search_orders_and_create_for_a_customer(): void
    {
        $admin = $this->makeUser(1);
        $customer = $this->makeUser(3, ['name' => 'Ana Maputo']);
        $this->makeOrder($customer, ['code' => 'KNG-SEARCH-1']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/orders?search=KNG-SEARCH')
            ->assertOk()
            ->assertJsonPath('data.data.0.code', 'KNG-SEARCH-1');

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/orders', [
                'user_id' => $customer->id,
                'order_type_id' => 2,
                'origin' => 'Porto de Maputo',
                'destination' => 'Matola',
                'delivery_fee' => 250,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.user_id', $customer->id)
            ->assertJsonPath('data.order_type_id', 2);
    }

    public function test_customer_cannot_create_order_for_another_user(): void
    {
        $customer = $this->makeUser(3);
        $other = $this->makeUser(3);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'user_id' => $other->id,
                'order_type_id' => 1,
                'origin' => 'A',
                'destination' => 'B',
                'delivery_fee' => 50,
            ])
            ->assertForbidden();
    }

    public function test_dispatch_board_retry_and_unassign(): void
    {
        $admin = $this->makeUser(1);
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dispatch/board')
            ->assertOk()
            ->assertJsonPath('data.unassigned_count', 1);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/dispatch/orders/{$order->id}/retry")
            ->assertOk()
            ->assertJsonPath('data.driver', null);

        $driver = $this->makeUser(4, [
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/assign-driver", [
                'driver_id' => $driver->id,
                'vehicle_id' => 1,
            ])
            ->assertUnprocessable();

        \App\Models\Vehicle::create([
            'license_plate_number' => 'TST-1',
            'model' => 'Honda',
            'color' => 'Amarela',
            'vehicle_type_id' => 1,
            'driver_id' => $driver->id,
            'vehicle_status_id' => 1,
            'capacity' => 30,
            'year' => 2024,
        ]);
        $vehicle = \App\Models\Vehicle::first();

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/assign-driver", [
                'driver_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.agent_user_id', $driver->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/dispatch/orders/{$order->id}/unassign")
            ->assertOk()
            ->assertJsonPath('data.agent_user_id', null);
    }

    public function test_admin_can_upload_an_image(): void
    {
        Storage::fake('public');
        $admin = $this->makeUser(1);

        $this->actingAs($admin, 'sanctum')
            ->post('/api/v1/uploads', [
                'file' => UploadedFile::fake()->image('loja.jpg', 80, 80),
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['url', 'path']]);

        Storage::disk('public')->assertExists(
            $this->actingAs($admin, 'sanctum')
                ->post('/api/v1/uploads', [
                    'file' => UploadedFile::fake()->image('loja2.jpg', 80, 80),
                ], ['Accept' => 'application/json'])
                ->json('data.path')
        );
    }

    public function test_users_index_is_paginated_and_filterable(): void
    {
        $admin = $this->makeUser(1, ['name' => 'Admin One']);
        $this->makeUser(3, ['name' => 'Cliente Ana']);
        $this->makeUser(4, ['name' => 'Motorista Boa']);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/users?role=driver')
            ->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.name', 'Motorista Boa');
    }

    public function test_manager_can_cancel_pending_order(): void
    {
        $manager = $this->makeUser(2);
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer);

        $this->actingAs($manager, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.order_status.name', 'cancelled');
    }

    public function test_admin_can_filter_payments(): void
    {
        $admin = $this->makeUser(1);
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer, ['code' => 'KNG-PAY-99']);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 80,
            'payment_method' => 'mpesa',
            'status' => 'pending',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/payments?status=pending&search=KNG-PAY-99')
            ->assertOk()
            ->assertJsonPath('data.total', 1);
    }

    private function makeUser(int $roleId, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => $roleId,
            'is_active' => true,
            'is_online' => false,
            'password' => 'password',
        ], $overrides));
    }

    private function makeOrder(User $customer, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $customer->id,
            'order_type_id' => 1,
            'origin' => 'Shop Maputo',
            'destination' => 'Av. 24 de Julho, Maputo',
            'order_status_id' => 1,
            'total_price' => 100,
            'delivery_fee' => 20,
            'code' => 'KNG-ADM-'.$customer->id,
        ], $overrides));
    }
}
