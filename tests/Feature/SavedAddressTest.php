<?php

namespace Tests\Feature;

use App\Models\SavedAddress;
use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
    }

    public function test_customer_can_create_and_list_addresses(): void
    {
        $customer = $this->makeUser();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/addresses', [
                'label' => 'casa',
                'address' => 'Av. 24 de Julho, nº 450',
                'neighborhood' => 'Polana',
                'city' => 'Maputo',
                'reference' => 'Perto do Jardim dos Namorados',
            ])
            ->assertCreated()
            ->assertJsonPath('data.label_display', 'Casa')
            ->assertJsonPath('data.is_default', true)
            ->assertJsonPath('data.line', 'Av. 24 de Julho, nº 450, Polana, Maputo')
            ->assertJsonPath('data.city', 'Maputo');

        $this->withToken($token)
            ->getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.is_default', true);
    }

    public function test_first_address_is_always_default(): void
    {
        $customer = $this->makeUser();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/addresses', [
                'label' => 'trabalho',
                'address' => 'Av. Julius Nyerere',
                'city' => 'Maputo',
                'is_default' => false,
            ])
            ->assertCreated()
            ->assertJsonPath('data.is_default', true);
    }

    public function test_setting_a_new_default_unsets_the_previous_one(): void
    {
        $customer = $this->makeUser();
        $home = SavedAddress::create([
            'user_id' => $customer->id,
            'label' => 'casa',
            'address' => 'Av. 24 de Julho',
            'city' => 'Maputo',
            'is_default' => true,
        ]);
        $work = SavedAddress::create([
            'user_id' => $customer->id,
            'label' => 'trabalho',
            'address' => 'Av. Julius Nyerere',
            'city' => 'Maputo',
            'is_default' => false,
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson("/api/v1/addresses/{$work->id}/default")
            ->assertOk()
            ->assertJsonPath('data.id', $work->id)
            ->assertJsonPath('data.is_default', true);

        $this->assertFalse($home->fresh()->is_default);
        $this->assertTrue($work->fresh()->is_default);
    }

    public function test_deleting_the_default_promotes_another_address(): void
    {
        $customer = $this->makeUser();
        $home = SavedAddress::create([
            'user_id' => $customer->id,
            'label' => 'casa',
            'address' => 'Av. 24 de Julho',
            'city' => 'Maputo',
            'is_default' => true,
        ]);
        $work = SavedAddress::create([
            'user_id' => $customer->id,
            'label' => 'trabalho',
            'address' => 'Av. Julius Nyerere',
            'city' => 'Maputo',
            'is_default' => false,
        ]);
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->deleteJson("/api/v1/addresses/{$home->id}")
            ->assertOk();

        $this->assertDatabaseMissing('saved_addresses', ['id' => $home->id]);
        $this->assertTrue($work->fresh()->is_default);
    }

    public function test_customer_cannot_read_or_change_someone_elses_address(): void
    {
        $owner = $this->makeUser();
        $stranger = $this->makeUser();
        $address = SavedAddress::create([
            'user_id' => $owner->id,
            'label' => 'casa',
            'address' => 'Costa do Sol',
            'city' => 'Maputo',
            'is_default' => true,
        ]);
        $token = $stranger->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->withToken($token)
            ->putJson("/api/v1/addresses/{$address->id}", [
                'address' => 'Rua hackeada',
            ])
            ->assertNotFound();

        $this->withToken($token)
            ->deleteJson("/api/v1/addresses/{$address->id}")
            ->assertNotFound();
    }

    public function test_address_is_required(): void
    {
        $customer = $this->makeUser();
        $token = $customer->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/addresses', [
                'label' => 'casa',
            ])
            ->assertUnprocessable();
    }

    private function makeUser(): User
    {
        return User::factory()->create([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ]);
    }
}
