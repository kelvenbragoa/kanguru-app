<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderException;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderExceptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_customer_can_report_and_admin_can_resolve_exception(): void
    {
        $customer = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);
        $admin = User::factory()->create(['role_id' => 1, 'is_active' => true, 'password' => 'password']);
        $shop = Shop::create([
            'name' => 'Restaurante Teste',
            'type' => 'restaurant',
            'address' => 'Beira',
            'delivery_fee' => '50',
            'email' => 'shop'.uniqid().'@test.mz',
            'phone' => '840000222',
        ]);

        $order = Order::create([
            'code' => 'ORD-EXC-1',
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'order_type_id' => 1,
            'order_status_id' => Order::statusId('delivered') ?? 8,
            'origin' => 'Loja',
            'destination' => 'Casa',
            'total_price' => 500,
            'delivery_fee' => 50,
        ]);

        $exceptionId = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/order-exceptions', [
                'order_id' => $order->id,
                'type' => 'incomplete_order',
                'description' => 'Faltou a bebida',
            ])
            ->assertCreated()
            ->assertJsonPath('data.cost_bearer', 'partner')
            ->assertJsonPath('data.status', 'open')
            ->json('data.id');

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/order-exceptions?status=open')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $exceptionId);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/order-exceptions/{$exceptionId}/resolve", [
                'cost_bearer' => 'partner',
                'cost_amount' => 80,
                'resolution_notes' => 'Reembolso parcial',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('order_exceptions', [
            'id' => $exceptionId,
            'status' => OrderException::STATUS_RESOLVED,
            'cost_amount' => 80,
        ]);
    }

    public function test_partner_reject_creates_exception(): void
    {
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true, 'password' => 'password']);
        $customer = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);
        $shop = Shop::create([
            'name' => 'Loja Owner',
            'type' => 'restaurant',
            'address' => 'Beira',
            'delivery_fee' => '40',
            'email' => 'owner'.uniqid().'@test.mz',
            'phone' => '840000333',
            'owner_user_id' => $owner->id,
        ]);

        $order = Order::create([
            'code' => 'ORD-EXC-2',
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'order_type_id' => 1,
            'order_status_id' => Order::statusId('pending') ?? 1,
            'origin' => 'Loja',
            'destination' => 'Casa',
            'total_price' => 300,
            'delivery_fee' => 40,
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/orders/{$order->id}/reject", [
                'reason' => 'Sem stock',
            ])
            ->assertOk();

        $this->assertDatabaseHas('order_exceptions', [
            'order_id' => $order->id,
            'type' => 'partner_refused',
            'status' => 'open',
        ]);
    }
}
