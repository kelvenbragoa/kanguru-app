<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Database\Seeders\VehicleStatusTableSeeder;
use Database\Seeders\VehicleTypeTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchTest extends TestCase
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

    public function test_online_driver_is_auto_assigned_when_order_is_created(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeOnlineDriver();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'origin' => 'Shop Maputo',
                'destination' => 'Av. 24 de Julho, Maputo',
                'delivery_fee' => 80,
            ])
            ->assertCreated()
            ->assertJsonPath('data.order_status_id', 3)
            ->assertJsonPath('data.agent_user_id', $driver->id);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $driver->id,
            'type' => 'order_assigned',
        ]);
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $customer->id,
            'type' => 'driver_assigned',
        ]);
    }

    public function test_order_stays_pending_when_no_driver_is_online(): void
    {
        $customer = $this->makeUser(3);
        $this->makeDriverWithVehicle(['is_online' => false]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'origin' => 'Shop Maputo',
                'destination' => 'Av. 24 de Julho, Maputo',
                'delivery_fee' => 80,
            ])
            ->assertCreated()
            ->assertJsonPath('data.order_status_id', 1)
            ->assertJsonPath('data.agent_user_id', null);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $customer->id,
            'type' => 'order_created',
        ]);
    }

    public function test_going_online_claims_a_pending_order(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeDriverWithVehicle(['is_online' => false]);
        $order = $this->makeOrder($customer);
        $token = $driver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/driver/availability', [
                'is_online' => true,
                'latitude' => -25.969248,
                'longitude' => 32.573176,
            ])
            ->assertOk()
            ->assertJsonPath('data.is_online', true)
            ->assertJsonPath('data.assigned_pending', 1);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'agent_user_id' => $driver->id,
            'order_status_id' => 3,
        ]);
    }

    public function test_driver_can_read_and_mark_notifications(): void
    {
        $driver = $this->makeOnlineDriver();
        $customer = $this->makeUser(3);
        $customerToken = $customer->createToken('test')->plainTextToken;

        $this->withToken($customerToken)
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'origin' => 'Shop Maputo',
                'destination' => 'Av. 24 de Julho, Maputo',
                'delivery_fee' => 50,
            ])
            ->assertCreated();

        $this->flushHeaders();

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonFragment(['type' => 'order_assigned']);

        $id = collect(
            $this->actingAs($driver, 'sanctum')->getJson('/api/v1/notifications')->json('data')
        )->firstWhere('type', 'order_assigned')['id'];

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread', 1);

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/notifications/{$id}/read")
            ->assertOk();

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread', 0);
    }

    public function test_heartbeat_keeps_driver_online_without_reassigning(): void
    {
        $driver = $this->makeOnlineDriver();
        $token = $driver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/driver/availability', [
                'is_online' => true,
                'latitude' => -25.97,
                'longitude' => 32.57,
            ])
            ->assertOk()
            ->assertJsonPath('data.assigned_pending', 0)
            ->assertJsonPath('data.is_online', true);

        $this->withToken($token)
            ->getJson('/api/v1/driver/availability')
            ->assertOk()
            ->assertJsonPath('data.is_online', true);
    }

    public function test_logout_sets_driver_offline(): void
    {
        $driver = $this->makeOnlineDriver();
        $token = $driver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $driver->id,
            'is_online' => 0,
        ]);
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

    private function makeDriverWithVehicle(array $overrides = []): User
    {
        $driver = $this->makeUser(4, $overrides);
        Vehicle::create([
            'license_plate_number' => 'TST-'.$driver->id,
            'model' => 'Honda CG 160',
            'color' => 'Vermelha',
            'vehicle_type_id' => 1,
            'driver_id' => $driver->id,
            'vehicle_status_id' => 1,
            'capacity' => 30,
            'year' => 2023,
        ]);

        return $driver->fresh();
    }

    private function makeOnlineDriver(): User
    {
        return $this->makeDriverWithVehicle([
            'is_online' => true,
            'last_seen_at' => now(),
            'last_latitude' => -25.969248,
            'last_longitude' => 32.573176,
            'location_updated_at' => now(),
        ]);
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
            'code' => 'KNG-DSP-'.$customer->id,
        ], $overrides));
    }
}
