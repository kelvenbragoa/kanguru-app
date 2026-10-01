<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderShowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_customer_can_read_own_order_detail(): void
    {
        $customer = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);
        $order = Order::create([
            'user_id' => $customer->id,
            'order_type_id' => 2,
            'origin' => 'Matola',
            'destination' => 'Maputo, Av. 24 de Julho',
            'order_status_id' => 1,
            'total_price' => 800,
            'delivery_fee' => 800,
            'notes' => 'Carga frágil',
            'code' => 'KNG-SHOW-1',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.code', 'KNG-SHOW-1')
            ->assertJsonPath('data.origin', 'Matola')
            ->assertJsonPath('data.destination', 'Maputo, Av. 24 de Julho')
            ->assertJsonPath('data.order_type.name', 'Transporte de Carga');
    }

    public function test_customer_cannot_read_someone_elses_order(): void
    {
        $owner = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);
        $stranger = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);
        $order = Order::create([
            'user_id' => $owner->id,
            'order_type_id' => 1,
            'origin' => 'Shop',
            'destination' => 'Casa',
            'order_status_id' => 1,
            'total_price' => 100,
            'delivery_fee' => 20,
            'code' => 'KNG-SHOW-2',
        ]);

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/v1/orders/{$order->id}")
            ->assertForbidden();
    }
}
