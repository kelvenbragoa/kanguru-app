<?php

namespace Tests\Feature;

use App\Models\Order;
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
use Tests\TestCase;

class BusinessPortalTest extends TestCase
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

    public function test_owner_sees_only_own_shop_orders_and_can_accept(): void
    {
        $owner = $this->makeUser(6);
        $other = $this->makeUser(6);
        $customer = $this->makeUser(3);

        $shop = Shop::create([
            'name' => 'Minha Loja',
            'address' => 'Beira',
            'delivery_fee' => '40',
            'owner_user_id' => $owner->id,
        ]);
        Shop::create([
            'name' => 'Outra',
            'address' => 'Beira',
            'delivery_fee' => '40',
            'owner_user_id' => $other->id,
        ]);

        $mine = Order::create([
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'order_type_id' => 1,
            'origin' => 'Minha Loja',
            'destination' => 'Casa',
            'order_status_id' => 1,
            'total_price' => 120,
            'delivery_fee' => 40,
            'code' => 'KNG-BIZ-1',
            'delivery_code' => '5555',
        ]);

        $this->actingAs($owner, 'sanctum')
            ->getJson('/api/v1/business/orders?inbox=1')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $mine->id);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/orders/{$mine->id}/shop-accept", [
                'preparation_minutes' => 12,
            ])
            ->assertOk()
            ->assertJsonPath('data.order_status.name', 'preparing');

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/v1/business/orders/{$mine->id}/shop-accept", [
                'preparation_minutes' => 10,
            ])
            ->assertForbidden();
    }

    public function test_owner_can_reject_and_stock_is_restored(): void
    {
        $owner = $this->makeUser(6);
        $customer = $this->makeUser(3);
        $shop = Shop::create([
            'name' => 'Loja',
            'address' => 'Beira',
            'delivery_fee' => '30',
            'owner_user_id' => $owner->id,
        ]);
        $category = ProductCategory::create(['name' => 'Pratos']);
        $product = Product::create([
            'shop_id' => $shop->id,
            'name' => 'Prato',
            'price' => 80,
            'product_category_id' => $category->id,
            'product_status_id' => ProductStatus::query()->where('name', 'Ativo')->value('id'),
            'stock_qty' => 10,
        ]);

        $orderId = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/orders', [
                'order_type_id' => 1,
                'shop_id' => $shop->id,
                'origin' => 'Loja',
                'destination' => 'Casa',
                'delivery_fee' => 30,
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2, 'price' => 80],
                ],
            ])
            ->assertCreated()
            ->json('data.id');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 8]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/orders/{$orderId}/reject")
            ->assertOk()
            ->assertJsonPath('data.order_status.name', 'cancelled');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock_qty' => 10]);
    }

    public function test_owner_can_manage_products(): void
    {
        $owner = $this->makeUser(6);
        $shop = Shop::create([
            'name' => 'Loja',
            'address' => 'Beira',
            'delivery_fee' => '25',
            'owner_user_id' => $owner->id,
            'email' => 'loja@test.mz',
            'phone' => '840000000',
        ]);
        $category = ProductCategory::create(['name' => 'Bebidas']);
        $statusId = ProductStatus::query()->where('name', 'Ativo')->value('id');

        $productId = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/business/products', [
                'shop_id' => $shop->id,
                'name' => 'Sumo',
                'price' => 50,
                'product_category_id' => $category->id,
                'product_status_id' => $statusId,
                'stock_qty' => 15,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/products/{$productId}/sold-out")
            ->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $productId,
            'stock_qty' => 0,
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
}
