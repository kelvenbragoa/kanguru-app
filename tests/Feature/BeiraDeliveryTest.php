<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductStatus;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\ProductStatusTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class BeiraDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
        $this->seed(ProductStatusTableSeeder::class);
    }

    public function test_shop_order_create_generates_delivery_code_and_reserves_stock(): void
    {
        [$customer, $shop, $product] = $this->seedShopCatalog(stock: 10);

        $response = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'shop_id' => $shop->id,
                'origin' => $shop->name,
                'destination' => 'Casa Beira',
                'delivery_fee' => 50,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                        'price' => 100,
                    ],
                ],
            ])
            ->assertCreated();

        $code = $response->json('data.delivery_code');
        $this->assertNotNull($code);
        $this->assertMatchesRegularExpression('/^\d{4}$/', $code);
        $this->assertNull($response->json('data.agent_user_id'));
        $this->assertSame('pending', $response->json('data.order_status.name'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_qty' => 8,
        ]);
    }

    public function test_cancel_restores_stock(): void
    {
        [$customer, $shop, $product] = $this->seedShopCatalog(stock: 5);

        $orderId = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'shop_id' => $shop->id,
                'origin' => $shop->name,
                'destination' => 'Casa',
                'delivery_fee' => 40,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 3, 'price' => 80],
                ],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 2]);

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/orders/{$orderId}/cancel")
            ->assertOk();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 5]);
    }

    public function test_advance_to_delivered_requires_matching_pin(): void
    {
        $customer = $this->makeUser(3);
        $driver = $this->makeUser(4);
        $shop = Shop::create([
            'name' => 'Loja PIN',
            'address' => 'Beira',
            'delivery_fee' => '30',
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'agent_user_id' => $driver->id,
            'order_type_id' => 1,
            'origin' => 'Loja',
            'destination' => 'Casa',
            'order_status_id' => OrderStatus::query()->where('name', 'delivering')->value('id'),
            'total_price' => 100,
            'delivery_fee' => 30,
            'code' => 'KNG-PIN-1',
            'delivery_code' => '4242',
        ]);

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$order->id}/status", [
                'delivery_code' => '0000',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Código de entrega incorrecto');

        $this->actingAs($driver, 'sanctum')
            ->postJson("/api/v1/driver/orders/{$order->id}/status", [
                'delivery_code' => '4242',
            ])
            ->assertOk()
            ->assertJsonPath('data.order_status.name', 'delivered');
    }

    public function test_shop_accept_schedules_courier_call(): void
    {
        $admin = $this->makeUser(1);
        $customer = $this->makeUser(3);
        $shop = Shop::create([
            'name' => 'Restaurante',
            'address' => 'Beira',
            'delivery_fee' => '40',
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'order_type_id' => 1,
            'origin' => 'Restaurante',
            'destination' => 'Casa',
            'order_status_id' => 1,
            'total_price' => 150,
            'delivery_fee' => 40,
            'code' => 'KNG-SHOP-1',
            'delivery_code' => '1111',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/shop-accept", [
                'preparation_minutes' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('data.order_status.name', 'preparing')
            ->assertJsonPath('data.preparation_minutes', 30);

        $order->refresh();
        $this->assertNotNull($order->courier_call_at);
        $this->assertNotNull($order->shop_accepted_at);
        $this->assertTrue($order->courier_call_at->greaterThan(now()->addMinutes(20)));

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/shop-accept", [
                'preparation_minutes' => 10,
            ])
            ->assertUnprocessable();
    }

    public function test_shop_accept_with_short_prep_calls_courier_immediately(): void
    {
        $admin = $this->makeUser(1);
        $customer = $this->makeUser(3);
        $shop = Shop::create([
            'name' => 'Fast',
            'address' => 'Beira',
            'delivery_fee' => '25',
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'order_type_id' => 1,
            'origin' => 'Fast',
            'destination' => 'Casa',
            'order_status_id' => 1,
            'total_price' => 90,
            'delivery_fee' => 25,
            'code' => 'KNG-FAST-1',
            'delivery_code' => '2222',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/orders/{$order->id}/shop-accept", [
                'preparation_minutes' => 10,
            ])
            ->assertOk();

        $order->refresh();
        $this->assertTrue($order->courier_call_at->lte(now()->addSecond()));
    }

    public function test_dispatch_due_couriers_command_runs(): void
    {
        $exit = Artisan::call('kanguro:dispatch-due-couriers');
        $this->assertSame(0, $exit);
    }

    private function seedShopCatalog(int $stock): array
    {
        $customer = $this->makeUser(3);
        $shop = Shop::create([
            'name' => 'Loja Beira',
            'address' => 'Beira',
            'delivery_fee' => '50',
        ]);
        $category = ProductCategory::create(['name' => 'Comida']);
        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Prato',
            'price' => 100,
            'product_category_id' => $category->id,
            'product_status_id' => ProductStatus::query()->where('name', 'Ativo')->value('id'),
            'stock_qty' => $stock,
            'low_stock_threshold' => 2,
        ]);

        return [$customer, $shop, $product];
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
            'password' => 'password',
        ]);
    }
}
