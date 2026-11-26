<?php

namespace Tests\Feature;

use App\Models\ServiceBooking;
use App\Models\ServiceOffering;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
    }

    public function test_customer_can_book_service_and_provider_completes_with_code(): void
    {
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true, 'password' => 'password']);
        [$offering, $customer] = $this->seedService(ownerId: $owner->id);

        $this->getJson('/api/v1/services')
            ->assertOk()
            ->assertJsonPath('data.data.0.title', $offering->title);

        $booking = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/service-bookings', [
                'service_offering_id' => $offering->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
                'address' => 'Bairro Ponta Gea, Beira',
                'payment_method' => 'mpesa',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->json('data');

        $this->assertNotEmpty($booking['completion_code']);

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/service-bookings/{$booking['id']}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/service-bookings/{$booking['id']}/start")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_progress');

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/service-bookings/{$booking['id']}/complete", [
                'completion_code' => $booking['completion_code'],
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/reviews', [
                'shop_id' => $offering->shop_id,
                'service_booking_id' => $booking['id'],
                'rating' => 5,
                'comment' => 'Excelente',
            ])
            ->assertOk()
            ->assertJsonPath('data.rating', 5);
    }

    public function test_complete_rejects_wrong_code(): void
    {
        $owner = User::factory()->create(['role_id' => 6, 'is_active' => true, 'password' => 'password']);
        [$offering, $customer] = $this->seedService(ownerId: $owner->id);

        $bookingId = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/service-bookings', [
                'service_offering_id' => $offering->id,
                'scheduled_at' => now()->addDay()->toIso8601String(),
                'address' => 'Beira',
            ])
            ->assertCreated()
            ->json('data.id');

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/service-bookings/{$bookingId}/accept")
            ->assertOk();

        $this->actingAs($owner, 'sanctum')
            ->postJson("/api/v1/business/service-bookings/{$bookingId}/complete", [
                'completion_code' => '0000',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Código de conclusão incorrecto');
    }

    private function seedService(?int $ownerId = null): array
    {
        $customer = User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);

        $shop = Shop::create([
            'name' => 'Beira Fix',
            'type' => Shop::TYPE_SERVICES,
            'address' => 'Beira',
            'delivery_fee' => '0',
            'email' => 'services'.uniqid().'@test.mz',
            'phone' => '840000111',
            'owner_user_id' => $ownerId,
        ]);

        $offering = ServiceOffering::create([
            'shop_id' => $shop->id,
            'title' => 'Reparação eléctrica',
            'category' => 'técnico',
            'description' => 'Visita ao local',
            'price' => 1500,
            'duration_minutes' => 90,
            'location_type' => 'onsite',
            'is_active' => true,
        ]);

        return [$offering, $customer];
    }
}
