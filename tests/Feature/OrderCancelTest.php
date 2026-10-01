<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCancelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_customer_can_cancel_own_pending_order(): void
    {
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'payment_method' => 'mpesa',
            'status' => 'pending',
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.order_status.name', 'cancelled');

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status_id' => 9,
        ]);
        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'failed',
        ]);
        $this->assertDatabaseHas('tracking_orders', [
            'order_id' => $order->id,
            'order_status_id' => 9,
        ]);
    }

    public function test_customer_cannot_cancel_someone_elses_order(): void
    {
        $owner = $this->makeUser(3);
        $stranger = $this->makeUser(3);
        $order = $this->makeOrder($owner);
        $token = $stranger->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertForbidden();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_status_id' => 1,
        ]);
    }

    public function test_customer_cannot_cancel_after_collection(): void
    {
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer, ['order_status_id' => 5]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot cancel this order after collection');
    }

    public function test_customer_cannot_cancel_delivered_order(): void
    {
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer, ['order_status_id' => 8]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cannot cancel this order');
    }

    public function test_completed_payment_is_marked_refunded_on_cancel(): void
    {
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'payment_method' => 'mpesa',
            'status' => 'completed',
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'status' => 'refunded',
        ]);
    }

    public function test_assigned_driver_is_notified_when_customer_cancels(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeOrder($customer, [
            'agent_user_id' => $driver->id,
            'order_status_id' => 3,
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/orders/{$order->id}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $driver->id,
            'type' => 'order_cancelled',
        ]);
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'password' => 'password',
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
            'code' => 'KNG-CANCEL-'.$customer->id,
        ], $overrides));
    }
}
