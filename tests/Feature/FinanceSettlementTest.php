<?php

namespace Tests\Feature;

use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use App\Services\SettlementService;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceSettlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_delivery_settlement_splits_partner_courier_and_platform(): void
    {
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true]);
        $driver = User::factory()->create(['role_id' => 4, 'is_active' => true]);
        $customer = User::factory()->create(['role_id' => 3, 'is_active' => true]);

        $shop = Shop::create([
            'name' => 'Loja Nacional',
            'type' => 'restaurant',
            'city' => 'Maputo',
            'address' => 'Maputo',
            'delivery_fee' => '100',
            'commission_rate' => 0.15,
            'email' => 'loja'.uniqid().'@test.mz',
            'phone' => '840000001',
            'owner_user_id' => $owner->id,
        ]);

        $order = Order::create([
            'code' => 'KNG-FIN-1',
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 1,
            'order_status_id' => Order::statusId('delivered') ?? 8,
            'origin' => 'Loja',
            'destination' => 'Casa',
            'total_price' => 1100,
            'delivery_fee' => 100,
            'service_fee' => 0,
            'delivered_at' => now(),
        ]);

        app(SettlementService::class)->settleOrder($order->fresh(['shop', 'orderType', 'orderItems', 'orderStatus']));

        $order->refresh();
        $this->assertNotNull($order->settled_at);
        $this->assertEquals(150.0, (float) $order->commission_amount);
        $this->assertEquals(850.0, (float) $order->partner_due);
        $this->assertEquals(80.0, (float) $order->courier_due);

        $this->assertDatabaseHas('ledger_entries', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'beneficiary_type' => LedgerEntry::TYPE_PARTNER,
            'amount' => 850,
        ]);
        $this->assertDatabaseHas('ledger_entries', [
            'reference_type' => 'order',
            'reference_id' => $order->id,
            'beneficiary_type' => LedgerEntry::TYPE_COURIER,
            'amount' => 80,
        ]);
    }

    public function test_admin_can_create_and_pay_payout_cycle(): void
    {
        $admin = User::factory()->create(['role_id' => 1, 'is_active' => true]);
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true]);
        $shop = Shop::create([
            'name' => 'Parceiro',
            'type' => 'restaurant',
            'city' => 'Beira',
            'address' => 'Beira',
            'email' => 'p'.uniqid().'@test.mz',
            'phone' => '840000002',
            'owner_user_id' => $owner->id,
            'delivery_fee' => '50',
        ]);

        LedgerEntry::create([
            'reference_type' => 'order',
            'reference_id' => 1,
            'beneficiary_type' => LedgerEntry::TYPE_PARTNER,
            'beneficiary_id' => $owner->id,
            'shop_id' => $shop->id,
            'user_id' => $owner->id,
            'amount' => 200,
            'status' => LedgerEntry::STATUS_PAYABLE,
            'description' => 'Teste',
        ]);

        $payoutId = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/v1/finance/payouts', [
                'period_start' => now()->startOfMonth()->toDateString(),
                'period_end' => now()->toDateString(),
                'beneficiary_type' => 'partner',
            ])
            ->assertCreated()
            ->json('data.0.id');

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/finance/payouts/{$payoutId}/pay")
            ->assertOk()
            ->assertJsonPath('data.status', 'paid');

        $this->assertDatabaseHas('ledger_entries', [
            'shop_id' => $shop->id,
            'status' => LedgerEntry::STATUS_PAID,
            'payout_id' => $payoutId,
        ]);
    }

    public function test_shops_filter_by_city_nationwide(): void
    {
        Shop::create([
            'name' => 'Maputo Grill',
            'type' => 'restaurant',
            'city' => 'Maputo',
            'address' => 'Maputo',
            'email' => 'm'.uniqid().'@test.mz',
            'phone' => '840000003',
            'delivery_fee' => '40',
            'is_featured' => true,
        ]);
        Shop::create([
            'name' => 'Beira Grill',
            'type' => 'restaurant',
            'city' => 'Beira',
            'address' => 'Beira',
            'email' => 'b'.uniqid().'@test.mz',
            'phone' => '840000004',
            'delivery_fee' => '40',
        ]);

        $this->getJson('/api/v1/shops?city=Maputo')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Maputo Grill')
            ->assertJsonCount(1, 'data.data');
    }
}
