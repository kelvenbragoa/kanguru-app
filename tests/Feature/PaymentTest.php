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

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_creating_an_order_with_mpesa_creates_a_pending_payment(): void
    {
        $customer = $this->makeUser(3);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'origin' => 'Shop Maputo',
                'destination' => 'Av. 24 de Julho, Maputo',
                'delivery_fee' => 80,
                'payment_method' => 'mpesa',
                'payer_phone' => '841234567',
            ])
            ->assertCreated()
            ->assertJsonPath('data.payments.0.payment_method', 'mpesa')
            ->assertJsonPath('data.payments.0.status', 'pending')
            ->assertJsonPath('data.payments.0.currency', 'MZN')
            ->assertJsonPath('data.payments.0.method_label', 'M-Pesa');

        $this->assertDatabaseHas('payments', [
            'payment_method' => 'mpesa',
            'status' => 'pending',
            'amount' => 80,
        ]);
    }

    public function test_pix_is_rejected_as_a_payment_method(): void
    {
        $customer = $this->makeUser(3);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'origin' => 'Shop',
                'destination' => 'Casa',
                'delivery_fee' => 50,
                'payment_method' => 'pix',
            ])
            ->assertUnprocessable();
    }

    public function test_customer_can_confirm_mpesa_payment(): void
    {
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'payment_method' => 'mpesa',
            'status' => 'pending',
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/payments/{$payment->id}/confirm", [
                'transaction_id' => 'MPESA-ABC',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.transaction_id', 'MPESA-ABC');
    }

    public function test_customer_cannot_confirm_cash_before_delivery(): void
    {
        $customer = $this->makeUser(3);
        $order = $this->makeOrder($customer);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/payments/{$payment->id}/confirm")
            ->assertForbidden();
    }

    public function test_delivering_an_order_completes_cash_payment(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $order = $this->makeOrder($customer, [
            'agent_user_id' => $driver->id,
            'order_status_id' => 7,
        ]);
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 100,
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);
        $token = $driver->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/driver/orders/{$order->id}/status")
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'completed',
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
            'code' => 'KNG-PAY-'.$customer->id,
        ], $overrides));
    }
}
