<?php

namespace Tests\Feature;

use App\Models\HotelBooking;
use App\Models\RoomType;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotelBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
    }

    public function test_customer_can_quote_and_book_room(): void
    {
        [$hotel, $room, $customer] = $this->seedHotel();

        $checkIn = now()->addDays(3)->toDateString();
        $checkOut = now()->addDays(5)->toDateString();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/hotel/quote', [
                'room_type_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
            ])
            ->assertOk()
            ->assertJsonPath('data.nights', 2)
            ->assertJsonPath('data.total_price', 3000);

        $bookingId = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/hotel/bookings', [
                'room_type_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 2,
                'payment_method' => 'mpesa',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->json('data.id');

        $this->assertDatabaseHas('hotel_bookings', [
            'id' => $bookingId,
            'shop_id' => $hotel->id,
            'nights' => 2,
        ]);

        $this->getJson("/api/v1/shops/{$hotel->id}/rooms")
            ->assertOk()
            ->assertJsonCount(1, 'data.rooms');
    }

    public function test_overlapping_booking_is_rejected_when_sold_out(): void
    {
        [, $room, $customer] = $this->seedHotel(quantity: 1);

        $checkIn = now()->addDays(2)->toDateString();
        $checkOut = now()->addDays(4)->toDateString();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/hotel/bookings', [
                'room_type_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 1,
            ])
            ->assertCreated();

        $other = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);

        $this->actingAs($other, 'sanctum')
            ->postJson('/api/v1/hotel/bookings', [
                'room_type_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 1,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Sem quartos livres nestas datas.');
    }

    public function test_hotel_owner_can_check_in_with_code(): void
    {
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true, 'password' => 'password']);
        [$hotel, $room, $customer] = $this->seedHotel(ownerId: $owner->id);

        $booking = HotelBooking::create([
            'code' => 'HTL-TEST-1',
            'checkin_code' => '7788',
            'user_id' => $customer->id,
            'shop_id' => $hotel->id,
            'room_type_id' => $room->id,
            'check_in' => now()->addDay()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'guests' => 2,
            'nights' => 1,
            'price_per_night' => 1500,
            'total_price' => 1500,
            'status' => HotelBooking::STATUS_CONFIRMED,
            'confirmed_at' => now(),
        ]);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/hotel-bookings/{$booking->id}/check-in", [
                'checkin_code' => '0000',
            ])
            ->assertUnprocessable();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/hotel-bookings/{$booking->id}/check-in", [
                'checkin_code' => '7788',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'checked_in');
    }

    public function test_cancel_frees_availability(): void
    {
        [, $room, $customer] = $this->seedHotel(quantity: 1);
        $checkIn = now()->addDays(6)->toDateString();
        $checkOut = now()->addDays(7)->toDateString();

        $id = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/hotel/bookings', [
                'room_type_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 1,
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($customer, 'sanctum')
            ->postJson("/api/v1/hotel/bookings/{$id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');

        $other = User::factory()->create(['role_id' => 3, 'is_active' => true, 'password' => 'password']);
        $this->actingAs($other, 'sanctum')
            ->postJson('/api/v1/hotel/bookings', [
                'room_type_id' => $room->id,
                'check_in' => $checkIn,
                'check_out' => $checkOut,
                'guests' => 1,
            ])
            ->assertCreated();
    }

    private function seedHotel(int $quantity = 3, ?int $ownerId = null): array
    {
        $customer = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);

        $hotel = Shop::create([
            'name' => 'Hotel Beira',
            'type' => 'hotel',
            'address' => 'Beira',
            'delivery_fee' => '0',
            'email' => 'hotel'.uniqid().'@test.mz',
            'phone' => '840000000',
            'owner_user_id' => $ownerId,
        ]);

        $room = RoomType::create([
            'shop_id' => $hotel->id,
            'name' => 'Duplo',
            'capacity' => 2,
            'quantity' => $quantity,
            'price_per_night' => 1500,
            'is_active' => true,
            'free_cancel_days' => 2,
        ]);

        return [$hotel, $room, $customer];
    }
}
