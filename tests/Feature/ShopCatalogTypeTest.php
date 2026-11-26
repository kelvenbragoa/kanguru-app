<?php

namespace Tests\Feature;

use App\Models\Shop;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_shops_can_be_filtered_by_type(): void
    {
        Shop::create([
            'name' => 'Sabor Beira',
            'type' => 'restaurant',
            'address' => 'Beira',
            'delivery_fee' => '40',
            'email' => 'sabor@test.mz',
            'phone' => '841111111',
        ]);
        Shop::create([
            'name' => 'Mercado Central',
            'type' => 'market',
            'address' => 'Beira',
            'delivery_fee' => '50',
            'email' => 'mercado@test.mz',
            'phone' => '842222222',
        ]);
        Shop::create([
            'name' => 'Farmácia Vida',
            'type' => 'pharmacy',
            'address' => 'Beira',
            'delivery_fee' => '30',
            'email' => 'farma@test.mz',
            'phone' => '843333333',
        ]);

        $this->getJson('/api/v1/shops?type=market')
            ->assertOk()
            ->assertJsonPath('data.data.0.name', 'Mercado Central')
            ->assertJsonPath('data.data.0.type', 'market')
            ->assertJsonPath('data.data.0.type_label', 'Mercado')
            ->assertJsonCount(1, 'data.data');

        $this->getJson('/api/v1/shops?type=pharmacy')
            ->assertOk()
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Farmácia Vida');

        $this->getJson('/api/v1/shops?type=hotel')
            ->assertOk()
            ->assertJsonCount(0, 'data.data');

        $this->getJson('/api/v1/shops')
            ->assertOk()
            ->assertJsonCount(3, 'data.data');
    }

    public function test_shop_defaults_to_restaurant_type(): void
    {
        $shop = Shop::create([
            'name' => 'Sem tipo',
            'address' => 'Beira',
            'delivery_fee' => '20',
            'email' => 'sem@test.mz',
            'phone' => '844444444',
        ]);

        $this->assertSame('restaurant', $shop->fresh()->type);
        $this->assertSame('Restaurante', $shop->fresh()->type_label);
    }
}
