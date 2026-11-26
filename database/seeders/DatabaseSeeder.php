<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Não usa factories/Faker — funciona em produção com `composer install --no-dev`.
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

        User::updateOrCreate(
            ['email' => 'admin@kanguru.com'],
            array_merge($defaults, [
                'name' => 'Admin User',
                'role_id' => 1,
            ])
        );

        User::updateOrCreate(
            ['email' => 'motorista@kanguru.com'],
            array_merge($defaults, [
                'name' => 'João Motorista',
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

        $this->call(DemoDataSeeder::class);
    }
}
