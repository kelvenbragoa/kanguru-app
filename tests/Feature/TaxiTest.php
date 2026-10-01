<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderType;
use App\Models\User;
use App\Models\Vehicle;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Database\Seeders\VehicleStatusTableSeeder;
use Database\Seeders\VehicleTypeTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxiTest extends TestCase
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

    public function test_quote_uses_base_plus_distance(): void
    {
        $customer = $this->makeUser(3);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/taxi/quote', [
                'origin_latitude' => 0,
                'origin_longitude' => 0,
                'destination_latitude' => 0,
                'destination_longitude' => 0,
            ])
            ->assertOk()
            ->assertJsonPath('data.distance_km', 0)
            ->assertJsonPath('data.amount', 80);
    }

    public function test_customer_taxi_order_stores_server_price(): void
    {
        $customer = $this->makeUser(3);
        $typeId = OrderType::where('name', 'Táxi')->value('id');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => $typeId,
                'origin' => 'Baixa',
                'destination' => 'Polana',
                'origin_latitude' => -25.965,
                'origin_longitude' => 32.573,
                'destination_latitude' => -25.970,
                'destination_longitude' => 32.590,
                'delivery_fee' => 1,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.customer_status_label', 'À procura de táxi');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertGreaterThan(80, (float) $order->delivery_fee);

        $this->getJson('/api/v1/tracking/'.$order->code)
            ->assertOk()
            ->assertJsonPath('data.order.code', $order->code);

        $this->getJson('/api/v1/tracking/'.$order->id)
            ->assertOk()
            ->assertJsonPath('data.order.code', $order->code);
        $this->assertNotSame(1.0, (float) $order->delivery_fee);
    }

    public function test_taxi_waits_for_the_driver_to_accept(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeDriver(2);
        $driver->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);
        $typeId = OrderType::where('name', 'Táxi')->value('id');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => $typeId,
                'origin' => 'Baixa',
                'destination' => 'Polana',
                'origin_latitude' => -25.965,
                'origin_longitude' => 32.573,
                'destination_latitude' => -25.970,
                'destination_longitude' => 32.590,
                'delivery_fee' => 1,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.agent_user_id', null)
            ->assertJsonPath('data.order_status_id', 1);

        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $driver->id,
            'type' => 'order_available',
        ]);
    }

    public function test_taxi_is_offered_only_to_moto_and_car(): void
    {
        $customer = $this->makeUser(3);
        $van = $this->makeDriver(3);
        $car = $this->makeDriver(2);
        $typeId = OrderType::where('name', 'Táxi')->value('id');

        $order = Order::create([
            'user_id' => $customer->id,
            'order_type_id' => $typeId,
            'origin' => 'Baixa',
            'destination' => 'Sommerschield',
            'order_status_id' => 1,
            'total_price' => 120,
            'delivery_fee' => 120,
            'code' => 'KNG-TAXI-1',
        ]);

        $this->actingAs($van, 'sanctum')
            ->getJson('/api/v1/driver/available-orders')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($van, 'sanctum')
            ->postJson('/api/v1/driver/orders/'.$order->id.'/accept')
            ->assertStatus(422);

        $this->actingAs($car, 'sanctum')
            ->getJson('/api/v1/driver/available-orders')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_taxi_trip_ends_when_passenger_arrives(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeDriver(2);
        $typeId = OrderType::where('name', 'Táxi')->value('id');
        $order = Order::create([
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => $typeId,
            'origin' => 'Baixa',
            'destination' => 'Costa do Sol',
            'order_status_id' => 3,
            'total_price' => 150,
            'delivery_fee' => 150,
            'code' => 'KNG-TAXI-2',
        ]);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/orders/'.$order->id.'/status')
            ->assertOk()
            ->assertJsonPath('data.order_status_id', 4);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/orders/'.$order->id.'/status')
            ->assertOk()
            ->assertJsonPath('data.order_status_id', 5);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/orders/'.$order->id.'/status')
            ->assertOk()
            ->assertJsonPath('data.order_status_id', 8);
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'password' => 'password',
        ]);
    }

    private function makeDriver(int $vehicleTypeId): User
    {
        $driver = $this->makeUser(4);
        Vehicle::create([
            'license_plate_number' => 'TX-'.$driver->id,
            'model' => 'Teste',
            'color' => 'Branco',
            'vehicle_type_id' => $vehicleTypeId,
            'driver_id' => $driver->id,
            'vehicle_status_id' => 1,
        ]);

        return $driver;
    }
}
