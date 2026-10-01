<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\SupportMessage;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Database\Seeders\VehicleStatusTableSeeder;
use Database\Seeders\VehicleTypeTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverAccountTest extends TestCase
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

    public function test_driver_saves_and_reads_own_vehicle(): void
    {
        $driver = $this->makeUser(4);

        $this->actingAs($driver, 'sanctum')
            ->putJson('/api/v1/driver/vehicle', [
                'license_plate_number' => 'ABC-1234',
                'model' => 'Honda CG 160',
                'color' => 'Vermelha',
                'vehicle_type_id' => 1,
            ])
            ->assertOk()
            ->assertJsonPath('data.model', 'Honda CG 160');

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/driver/vehicle')
            ->assertOk()
            ->assertJsonPath('data.vehicle.license_plate_number', 'ABC-1234')
            ->assertJsonPath('data.vehicle.vehicle_type.name', 'Moto');

        $this->assertSame(1, Vehicle::where('driver_id', $driver->id)->count());
    }

    public function test_settlement_sums_cash_collected_on_delivered_orders(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeOrder($customer, $driver, 8);
        Payment::create([
            'order_id' => $order->id,
            'amount' => 350,
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/driver/settlement')
            ->assertOk()
            ->assertJsonPath('data.amount', 350)
            ->assertJsonPath('data.count', 1);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/settlement', ['note' => 'Entrego hoje'])
            ->assertCreated();

        $this->assertDatabaseHas('support_messages', [
            'user_id' => $driver->id,
            'subject' => 'Fecho de conta',
        ]);
        $this->assertTrue(
            str_contains((string) SupportMessage::first()->message, '350')
        );
    }

    public function test_driver_can_return_an_order_before_pickup(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeOrder($customer, $driver, 3);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/orders/'.$order->id.'/release')
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'agent_user_id' => null,
            'order_status_id' => 2,
        ]);
    }

    public function test_driver_cannot_return_a_collected_order(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeOrder($customer, $driver, 5);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/orders/'.$order->id.'/release')
            ->assertStatus(422);
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'password' => 'password',
        ]);
    }

    private function makeOrder(User $customer, User $driver, int $statusId): Order
    {
        return Order::create([
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 1,
            'origin' => 'Loja',
            'destination' => 'Polana',
            'order_status_id' => $statusId,
            'total_price' => 100,
            'delivery_fee' => 50,
            'code' => 'KNG-DRV-'.uniqid(),
        ]);
    }
}
