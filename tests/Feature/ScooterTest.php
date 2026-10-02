<?php

namespace Tests\Feature;

use App\Models\Scooter;
use App\Models\ScooterRental;
use App\Models\ScooterSetting;
use App\Models\ScooterStation;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScooterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
    }

    public function test_admin_can_update_scooter_pricing(): void
    {
        $admin = $this->makeUser(1);

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/v1/scooter-settings', [
                'unlock_fee' => 15,
                'price_per_minute' => 3,
                'minimum_amount' => 30,
            ])
            ->assertOk()
            ->assertJsonPath('data.unlock_fee', 15)
            ->assertJsonPath('data.price_per_minute', 3)
            ->assertJsonPath('data.minimum_amount', 30);

        $this->assertSame(15.0, (float) ScooterSetting::current()->unlock_fee);
    }

    public function test_customer_can_rent_and_return_at_a_station(): void
    {
        $customer = $this->makeUser(3);
        $settings = ScooterSetting::current();
        $settings->update([
            'unlock_fee' => 10,
            'price_per_minute' => 2,
            'minimum_amount' => 20,
        ]);

        $start = ScooterStation::query()->create([
            'name' => 'Baixa Teste',
            'address' => 'Maputo',
            'latitude' => -25.969248,
            'longitude' => 32.573176,
            'is_active' => true,
        ]);
        $end = ScooterStation::query()->create([
            'name' => 'Polana Teste',
            'address' => 'Maputo',
            'latitude' => -25.9648,
            'longitude' => 32.5894,
            'is_active' => true,
        ]);
        $scooter = Scooter::query()->create([
            'code' => 'TRT-TEST1',
            'scooter_station_id' => $start->id,
            'battery_percent' => 90,
            'status' => 'available',
        ]);

        $startResponse = $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/scooters/rentals/start', [
                'scooter_id' => $scooter->id,
                'payment_method' => 'cash',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.scooter.code', 'TRT-TEST1');

        $rentalId = $startResponse->json('data.id');
        $this->assertSame('rented', $scooter->fresh()->status);

        $this->travel(3)->minutes();

        $this->actingAs($customer, 'sanctum')
            ->postJson('/api/v1/scooters/rentals/'.$rentalId.'/end', [
                'station_id' => $end->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.amount', 20)
            ->assertJsonPath('data.payment_status', 'pending');

        $scooter->refresh();
        $this->assertSame('available', $scooter->status);
        $this->assertSame($end->id, $scooter->scooter_station_id);

        $rental = ScooterRental::find($rentalId);
        $this->assertNotNull($rental);
        $this->assertSame('completed', $rental->status);
        $this->assertSame(20.0, (float) $rental->amount);
    }

    private function makeUser(int $roleId): User
    {
        return User::factory()->create([
            'role_id' => $roleId,
            'is_active' => true,
        ]);
    }
}
