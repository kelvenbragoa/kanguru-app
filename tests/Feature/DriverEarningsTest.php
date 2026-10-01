<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverEarningsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_driver_earnings_sum_delivery_fees_of_delivered_orders(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $other = $this->makeUser(4);

        $this->makeDeliveredOrder($customer, $driver, 80);
        $this->makeDeliveredOrder($customer, $driver, 40);
        $this->makeDeliveredOrder($customer, $other, 99);
        Order::create([
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 1,
            'origin' => 'Shop',
            'destination' => 'Casa',
            'order_status_id' => 3,
            'total_price' => 100,
            'delivery_fee' => 50,
            'code' => 'KNG-OPEN',
        ]);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/driver/earnings?period=all')
            ->assertOk()
            ->assertJsonPath('data.period', 'all')
            ->assertJsonPath('data.total', 120)
            ->assertJsonPath('data.deliveries', 2)
            ->assertJsonPath('data.lifetime.total', 120)
            ->assertJsonPath('data.lifetime.deliveries', 2)
            ->assertJsonPath('data.month.deliveries', 2)
            ->assertJsonFragment(['amount' => 80]);
    }

    public function test_today_period_excludes_older_deliveries(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);

        $this->makeDeliveredOrder($customer, $driver, 80);
        $this->makeDeliveredOrder($customer, $driver, 25, now()->subDays(3));

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/driver/earnings?period=today')
            ->assertOk()
            ->assertJsonPath('data.period_label', 'Hoje')
            ->assertJsonPath('data.total', 80)
            ->assertJsonPath('data.deliveries', 1)
            ->assertJsonPath('data.lifetime.total', 105);
    }

    public function test_customer_cannot_read_driver_earnings(): void
    {
        $customer = $this->makeUser(3);

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/driver/earnings')
            ->assertForbidden();
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'password' => 'password',
        ]);
    }

    private function makeDeliveredOrder(
        User $customer,
        User $driver,
        float $fee,
        $deliveredAt = null
    ): Order {
        $deliveredAt ??= now();

        return Order::create([
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 1,
            'origin' => 'Shop Maputo',
            'destination' => 'Av. 24 de Julho, Maputo',
            'order_status_id' => 8,
            'total_price' => $fee + 50,
            'delivery_fee' => $fee,
            'collected_at' => $deliveredAt->copy()->subHour(),
            'delivered_at' => $deliveredAt,
            'code' => 'KNG-ERN-'.$driver->id.'-'.uniqid(),
        ]);
    }
}
