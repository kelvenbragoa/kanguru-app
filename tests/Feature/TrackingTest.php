<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_assigned_driver_can_ping_live_location(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeAssignedOrder($customer, $driver);
        $token = $driver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/driver/orders/{$order->id}/location", [
                'latitude' => -25.969248,
                'longitude' => 32.573176,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.last_location.latitude', -25.969248)
            ->assertJsonPath('data.last_location.longitude', 32.573176);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'current_latitude' => -25.969248,
            'current_longitude' => 32.573176,
        ]);
    }

    public function test_customer_can_read_driver_location_for_own_order(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeAssignedOrder($customer, $driver, [
            'current_latitude' => -25.97,
            'current_longitude' => 32.57,
            'location_updated_at' => now(),
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/tracking/orders/{$order->id}/location")
            ->assertOk()
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.last_location.latitude', -25.97)
            ->assertJsonPath('data.last_location.longitude', 32.57);
    }

    public function test_other_customer_cannot_read_driver_location(): void
    {
        $customer = $this->makeUser(3);
        $stranger = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeAssignedOrder($customer, $driver);
        $token = $stranger->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson("/api/v1/tracking/orders/{$order->id}/location")
            ->assertForbidden();
    }

    public function test_unassigned_driver_cannot_ping_location(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $otherDriver = $this->makeUser(4);
        $order = $this->makeAssignedOrder($customer, $driver);
        $token = $otherDriver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/driver/orders/{$order->id}/location", [
                'latitude' => -25.969248,
                'longitude' => 32.573176,
            ])
            ->assertForbidden();
    }

    public function test_public_tracking_includes_current_location(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeAssignedOrder($customer, $driver, [
            'code' => 'KNG-TEST-GPS',
            'current_latitude' => -25.969248,
            'current_longitude' => 32.573176,
            'location_updated_at' => now(),
        ]);

        $this->getJson("/api/v1/tracking/{$order->code}")
            ->assertOk()
            ->assertJsonPath('data.current_location.latitude', -25.969248)
            ->assertJsonPath('data.current_location.longitude', 32.573176);
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'password' => 'password',
        ]);
    }

    private function makeAssignedOrder(User $customer, User $driver, array $overrides = []): Order
    {
        return Order::create(array_merge([
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 1,
            'origin' => 'Shop Centro',
            'destination' => 'Rua das Flores, 123',
            'order_status_id' => 3,
            'total_price' => 100,
            'delivery_fee' => 20,
            'code' => 'KNG-TEST-'.$customer->id.'-'.$driver->id,
        ], $overrides));
    }
}
