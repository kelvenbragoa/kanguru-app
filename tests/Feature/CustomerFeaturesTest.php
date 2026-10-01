<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\OrderStatusTableSeeder;
use Database\Seeders\OrderTypeTableSeeder;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
        $this->seed(OrderStatusTableSeeder::class);
        $this->seed(OrderTypeTableSeeder::class);
    }

    public function test_customer_can_save_favorite_review_coupon_and_preferences(): void
    {
        $customer = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);
        $shop = Shop::create([
            'name' => 'Sabor',
            'address' => 'Maputo',
            'delivery_fee' => '50',
        ]);
        Order::create([
            'user_id' => $customer->id,
            'shop_id' => $shop->id,
            'order_type_id' => 1,
            'origin' => 'Sabor',
            'destination' => 'Casa',
            'order_status_id' => 8,
            'total_price' => 200,
            'delivery_fee' => 50,
            'discount_amount' => 20,
            'code' => 'KNG-REV-1',
        ]);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/favorites', ['shop_id' => $shop->id])
            ->assertCreated();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'shop_id' => $shop->id,
                'rating' => 5,
                'comment' => 'Muito bom',
            ])
            ->assertOk();

        $this->getJson('/api/v1/shops')
            ->assertOk()
            ->assertJsonPath('data.data.0.rating', 5);

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/coupons/validate', [
                'code' => 'yala10',
                'subtotal' => 100,
                'delivery_fee' => 50,
            ])
            ->assertOk()
            ->assertJsonPath('data.discount', 10);

        $this->actingAs($customer, 'sanctum')
            ->putJson('/api/v1/me/preferences', [
                'notify_push' => false,
                'notify_email' => true,
                'notify_sms' => false,
            ])
            ->assertOk();

        $this->actingAs($customer, 'sanctum')
            ->getJson('/api/v1/me/summary')
            ->assertOk()
            ->assertJsonPath('data.orders_count', 1)
            ->assertJsonPath('data.savings', 20)
            ->assertJsonPath('data.notify_email', true);
    }

    public function test_password_reset_code_changes_the_password_when_debug_is_on(): void
    {
        config(['app.debug' => true]);
        $customer = User::factory()->create([
            'role_id' => 3,
            'email' => 'cliente@kanguru.com',
            'is_active' => true,
            'password' => 'password',
        ]);

        $code = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => $customer->email,
        ])->assertOk()->json('debug_code');

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $customer->email,
            'code' => $code,
            'password' => 'nova-senha',
            'password_confirmation' => 'nova-senha',
        ])->assertOk();

        $this->postJson('/api/v1/auth/login', [
            'email' => $customer->email,
            'password' => 'nova-senha',
        ])->assertOk();
    }
}
