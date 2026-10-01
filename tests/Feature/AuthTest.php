<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleTableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleTableSeeder::class);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->getJson('/api/v1/auth/user')->assertUnauthorized();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', $user->email)
            ->assertJsonPath('data.user.role.name', 'customer');
    }

    public function test_user_alias_returns_the_authenticated_user(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/auth/user')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id);
    }

    public function test_user_can_update_profile(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/profile', [
                'name' => 'João Silva Santos',
                'phone' => '841234567',
                'city' => 'Maputo',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.user.name', 'João Silva Santos')
            ->assertJsonPath('data.user.profile.phone', '841234567')
            ->assertJsonPath('data.user.profile.city', 'Maputo');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'João Silva Santos',
        ]);
        $this->assertDatabaseHas('profiles', [
            'user_id' => $user->id,
            'phone' => '841234567',
            'city' => 'Maputo',
        ]);
    }

    public function test_user_cannot_change_role_via_profile(): void
    {
        $user = $this->makeUser(['role_id' => 3]);
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/profile', [
                'name' => 'Cliente',
                'role_id' => 1,
                'is_active' => false,
            ])
            ->assertOk();

        $user->refresh();
        $this->assertSame(3, $user->role_id);
        $this->assertTrue($user->is_active);
    }

    public function test_user_cannot_take_another_users_email(): void
    {
        $other = $this->makeUser(['email' => 'outro@kanguru.com']);
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/profile', [
                'email' => $other->email,
            ])
            ->assertUnprocessable();
    }

    public function test_user_can_change_password(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'new-password',
        ])->assertOk();
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)
            ->putJson('/api/v1/auth/change-password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_logout_revokes_the_current_token(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('mobile_app')->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful');

        $this->assertSame(0, $user->tokens()->count());

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }

    public function test_logout_all_revokes_every_token(): void
    {
        $user = $this->makeUser();
        $first = $user->createToken('device-1')->plainTextToken;
        $user->createToken('device-2');

        $this->assertSame(2, $user->tokens()->count());

        $this->withToken($first)
            ->postJson('/api/v1/auth/logout-all')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out from all devices');

        $this->assertSame(0, PersonalAccessToken::where('tokenable_id', $user->id)->count());
    }

    public function test_login_still_returns_a_bearer_token(): void
    {
        $user = $this->makeUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['user', 'token', 'token_type']]);
    }

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => 3,
            'is_active' => true,
            'password' => 'password',
        ], $overrides));
    }
}
