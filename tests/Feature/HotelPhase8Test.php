<?php

namespace Tests\Feature;

use App\Models\RoomType;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HotelPhase8Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
    }

    public function test_booking_starts_pending_and_hotel_can_confirm(): void
    {
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true, 'password' => 'password']);
        $customer = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);

        $shop = Shop::create([
            'name' => 'Hotel Nacional',
            'type' => Shop::TYPE_HOTEL,
            'owner_user_id' => $owner->id,
            'city' => 'Maputo',
            'address' => 'Maputo',
            'email' => 'hotel'.uniqid().'@test.mz',
            'phone' => '840000099',
        ]);

        $room = RoomType::create([
            'shop_id' => $shop->id,
            'name' => 'Duplo',
            'capacity' => 2,
            'quantity' => 3,
            'price_per_night' => 2000,
            'free_cancel_days' => 1,
            'deposit_percent' => 30,
            'is_active' => true,
        ]);

        Sanctum::actingAs($customer);
        $create = $this->postJson('/api/v1/hotel/bookings', [
            'room_type_id' => $room->id,
            'check_in' => now()->addDays(2)->toDateString(),
            'check_out' => now()->addDays(4)->toDateString(),
            'guests' => 2,
            'payment_mode' => 'deposit',
            'payment_method' => 'mpesa',
        ]);

        $create->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $bookingId = $create->json('data.id');
        $this->assertTrue((float) $create->json('data.deposit_amount') > 0);

        Sanctum::actingAs($owner);
        $this->postJson("/api/v1/business/hotel-bookings/{$bookingId}/confirm")
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }
}
