<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransportOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_customer_can_create_a_cargo_order_without_shop_or_items(): void
    {
        $customer = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => 2,
                'origin' => 'Matola, Bairro Liberdade',
                'destination' => 'Maputo, Av. Julius Nyerere',
                'delivery_fee' => 800,
                'weight' => 120,
                'notes' => 'Veículo: Van | Destinatário: Ana (841234567)',
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.order_type_id', 2)
            ->assertJsonPath('data.shop_id', null)
            ->assertJsonPath('data.total_price', '800.00')
            ->assertJsonPath('data.weight', '120.00')
            ->assertJsonPath('data.payments.0.payment_method', 'cash');
    }

    public function test_customer_can_create_a_moving_order(): void
    {
        $customer = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => 3,
                'origin' => 'Av. 24 de Julho, Maputo',
                'destination' => 'Costa do Sol, Maputo',
                'delivery_fee' => 2500,
                'notes' => 'Mudança de T2',
                'payment_method' => 'mpesa',
                'payer_phone' => '841234567',
            ])
            ->assertCreated()
            ->assertJsonPath('data.order_type_id', 3);
    }
}
