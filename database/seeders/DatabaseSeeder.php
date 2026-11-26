<?php

namespace Database\Seeders;

use App\Models\DriverVerification;
use App\Models\DriverWallet;
use App\Models\Profile;
use App\Models\User;
use App\Services\DriverWalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed base lookups + utilizadores demo alinhados com fases 7–8
     * (carteira, KYC, parceiro, perfis). Sem Faker — seguro em produção --no-dev.
     */
    public function run(): void
    {
        $this->call([
            RoleTableSeeder::class,
            VehicleTypeTableSeeder::class,
            VehicleStatusTableSeeder::class,
            OrderStatusTableSeeder::class,
            OrderTypeTableSeeder::class,
            ProductCategoryTableSeeder::class,
            ProductStatusTableSeeder::class,
        ]);

        $defaults = [
            'email_verified_at' => now(),
            'password' => 'password',
            'is_active' => true,
        ];

        $admin = User::updateOrCreate(
            ['email' => 'admin@kanguru.com'],
            array_merge($defaults, [
                'name' => 'Admin User',
                'role_id' => 1,
            ])
        );

        $driver = User::updateOrCreate(
            ['email' => 'motorista@kanguru.com'],
            array_merge($defaults, [
                'name' => 'João Motorista',
                'role_id' => 4,
            ])
        );

        $fleet = User::updateOrCreate(
            ['email' => 'frota@kanguru.com'],
            array_merge($defaults, [
                'name' => 'Carlos Frota',
                'role_id' => 4,
            ])
        );

        User::updateOrCreate(
            ['email' => 'cliente@kanguru.com'],
            array_merge($defaults, [
                'name' => 'Cliente Teste',
                'role_id' => 3,
            ])
        );

        User::updateOrCreate(
            ['email' => 'parceiro@kanguru.com'],
            array_merge($defaults, [
                'name' => 'Parceiro Demo',
                'role_id' => 6,
            ])
        );

        $profiles = [
            'admin@kanguru.com' => ['phone' => '840000000', 'city' => 'Beira'],
            'motorista@kanguru.com' => ['phone' => '840000001', 'city' => 'Beira'],
            'frota@kanguru.com' => ['phone' => '840000002', 'city' => 'Beira'],
            'cliente@kanguru.com' => ['phone' => '840000003', 'city' => 'Maputo'],
            'parceiro@kanguru.com' => ['phone' => '840000004', 'city' => 'Beira'],
        ];

        foreach ($profiles as $email => $data) {
            $userId = User::query()->where('email', $email)->value('id');
            if (! $userId) {
                continue;
            }
            $payload = $data;
            if (Schema::hasColumn('profiles', 'cash_blocked')) {
                $payload['cash_blocked'] = false;
            }
            Profile::updateOrCreate(['user_id' => $userId], $payload);
        }

        // Independente: KYC aprovado + saldo acima do mínimo
        DriverVerification::updateOrCreate(
            ['user_id' => $driver->id],
            [
                'license_number' => 'MZ-DEMO-001',
                'status' => DriverVerification::STATUS_APPROVED,
                'submitted_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
                'reviewed_by' => $admin->id,
                'admin_notes' => 'Aprovado no seed demo',
            ]
        );

        $walletService = app(DriverWalletService::class);
        $wallet = $walletService->ensureWallet($driver, DriverWallet::TYPE_INDEPENDENT);
        if ((float) $wallet->balance < 500) {
            $walletService->adminCredit($driver, 500, 'cash_office', 'Saldo inicial seed', $admin);
        }

        // Frota: sem exigência de carteira pré-paga
        DriverVerification::updateOrCreate(
            ['user_id' => $fleet->id],
            [
                'license_number' => 'MZ-DEMO-FLEET',
                'status' => DriverVerification::STATUS_APPROVED,
                'submitted_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
                'reviewed_by' => $admin->id,
                'admin_notes' => 'Frota aprovada no seed',
            ]
        );
        $fleetWallet = $walletService->ensureWallet($fleet, DriverWallet::TYPE_FLEET);
        $fleetWallet->update([
            'driver_type' => DriverWallet::TYPE_FLEET,
            'balance' => 0,
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
