<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Executar seeders na ordem correta (respeitando foreign keys)
        $this->call([
            RoleTableSeeder::class,
            VehicleTypeTableSeeder::class,
            VehicleStatusTableSeeder::class,
            OrderStatusTableSeeder::class,
            OrderTypeTableSeeder::class,
            ProductCategoryTableSeeder::class,
            ProductStatusTableSeeder::class,
        ]);

        // Criar usuário de teste com role de admin
        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@kanguru.com',
            'role_id' => 1, // Admin
        ]);

        // Criar usuário motorista de teste
        User::factory()->create([
            'name' => 'João Motorista',
            'email' => 'motorista@kanguru.com',
            'role_id' => 4, // Driver
        ]);

        // Criar usuário cliente de teste
        User::factory()->create([
            'name' => 'Cliente Teste',
            'email' => 'cliente@kanguru.com',
            'role_id' => 3, // Customer
        ]);

        $this->call(DemoDataSeeder::class);
    }
}
