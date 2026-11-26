<?php

namespace Tests\Feature;

use App\Models\DriverWallet;
use App\Models\DriverWalletTransaction;
use App\Models\Order;
use App\Models\User;
use App\Services\DriverWalletService;
use App\Services\SettlementService;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverWalletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
        config([
            'kanguro.driver_wallet_enabled' => true,
            'kanguro.driver_wallet_min_balance' => 200,
            'kanguro.driver_wallet_min_topup' => 50,
            'kanguro.taxi_platform_commission' => 0.20,
            'kanguro.require_driver_kyc' => false,
        ]);
    }

    public function test_independent_driver_blocked_online_without_balance(): void
    {
        $driver = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        app(DriverWalletService::class)->ensureWallet($driver);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/availability', ['is_online' => true])
            ->assertStatus(422)
            ->assertJsonPath('data.needs_topup', true);
    }

    public function test_topup_and_online_after_credit(): void
    {
        $driver = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        $admin = User::factory()->create(['role_id' => 1, 'is_active' => true]);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/wallet/topup', [
                'amount' => 500,
                'method' => 'mpesa',
                'external_ref' => 'MP123',
            ])
            ->assertCreated();

        $tx = DriverWalletTransaction::query()->where('user_id', $driver->id)->first();
        $this->assertSame(DriverWalletTransaction::STATUS_PENDING, $tx->status);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/driver-wallet-topups/'.$tx->id.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.balance', 500);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/availability', ['is_online' => true])
            ->assertOk()
            ->assertJsonPath('data.is_online', true);
    }

    public function test_taxi_settlement_debits_independent_wallet(): void
    {
        $driver = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        $customer = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $wallets = app(DriverWalletService::class);
        $wallets->adminCredit($driver, 500, 'cash_office', 'seed');

        $order = Order::create([
            'code' => 'KNG-WALLET-TAXI',
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 2, // Táxi (seed)
            'order_status_id' => Order::statusId('delivered') ?? 8,
            'origin' => 'A',
            'destination' => 'B',
            'total_price' => 1000,
            'delivery_fee' => 0,
            'service_fee' => 0,
            'delivered_at' => now(),
        ]);

        // Confirm order type name is Táxi
        $order->load('orderType');
        if ($order->orderType?->name !== 'Táxi') {
            $taxiTypeId = \App\Models\OrderType::query()->where('name', 'Táxi')->value('id') ?? 2;
            $order->update(['order_type_id' => $taxiTypeId]);
        }

        app(SettlementService::class)->settleOrder($order->fresh(['shop', 'orderType', 'orderItems', 'orderStatus']));

        $wallet = DriverWallet::query()->where('user_id', $driver->id)->first();
        // 20% of 1000 = 200 commission
        $this->assertEquals(300.0, (float) $wallet->balance);

        $this->assertDatabaseHas('driver_wallet_transactions', [
            'user_id' => $driver->id,
            'type' => DriverWalletTransaction::TYPE_COMMISSION,
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'amount' => -200,
        ]);
    }

    public function test_fleet_driver_skips_wallet_gate_and_commission_debit(): void
    {
        $driver = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        $customer = User::factory()->create(['role_id' => 3, 'is_active' => true]);
        $wallet = app(DriverWalletService::class)->ensureWallet($driver, DriverWallet::TYPE_FLEET);
        $wallet->update(['driver_type' => DriverWallet::TYPE_FLEET, 'balance' => 0]);

        $this->actingAs($driver, 'sanctum')
            ->postJson('/api/v1/driver/availability', ['is_online' => true])
            ->assertOk();

        $taxiTypeId = \App\Models\OrderType::query()->where('name', 'Táxi')->value('id') ?? 2;
        $order = Order::create([
            'code' => 'KNG-WALLET-FLEET',
            'user_id' => $customer->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => $taxiTypeId,
            'order_status_id' => Order::statusId('delivered') ?? 8,
            'origin' => 'A',
            'destination' => 'B',
            'total_price' => 1000,
            'delivery_fee' => 0,
            'delivered_at' => now(),
        ]);

        app(SettlementService::class)->settleOrder($order->fresh(['shop', 'orderType', 'orderItems', 'orderStatus']));

        $wallet->refresh();
        $this->assertEquals(0.0, (float) $wallet->balance);
        $this->assertDatabaseMissing('driver_wallet_transactions', [
            'user_id' => $driver->id,
            'type' => DriverWalletTransaction::TYPE_COMMISSION,
            'reference_id' => $order->id,
        ]);
    }

    public function test_admin_can_set_driver_type_and_credit(): void
    {
        $admin = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $driver = User::factory()->create(['role_id' => 4, 'is_active' => true]);

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/driver-wallets/'.$driver->id.'/type', [
                'driver_type' => 'independent',
                'min_balance' => 150,
            ])
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/driver-wallets/'.$driver->id.'/credit', [
                'amount' => 300,
                'method' => 'cash_office',
            ])
            ->assertOk()
            ->assertJsonPath('data.balance', 300);

        $this->actingAs($driver, 'sanctum')
            ->getJson('/api/v1/driver/wallet')
            ->assertOk()
            ->assertJsonPath('data.balance', 300)
            ->assertJsonPath('data.min_balance', 150);
    }
}
