<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Criar algumas lojas de exemplo
        DB::table('shops')->insert([
            [
                'name' => 'Restaurante Sabor & Arte',
                'address' => 'Rua das Flores, 123 - Centro',
                'phone' => '(11) 99999-1234',
                'email' => 'contato@saborarte.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Farmácia São João',
                'address' => 'Av. Principal, 456 - Bairro Alto',
                'phone' => '(11) 99999-5678',
                'email' => 'farmacia@saojoao.com',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Supermercado Economia',
                'address' => 'Rua do Comércio, 789 - Vila Nova',
                'phone' => '(11) 99999-9012',
                'email' => 'vendas@economia.com',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Criar alguns veículos de exemplo
        DB::table('vehicles')->insert([
            [
                'license_plate_number' => 'ABC-1234',
                'model' => 'Honda CG 160',
                'color' => 'Vermelha',
                'vehicle_type_id' => 1, // Moto
                'driver_id' => 2, // João Motorista
                'vehicle_status_id' => 1, // Disponível
                'capacity' => 30.00,
                'description' => 'Moto para delivery rápido',
                'year' => 2023,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'license_plate_number' => 'DEF-5678',
                'model' => 'Fiat Fiorino',
                'color' => 'Branca',
                'vehicle_type_id' => 3, // Van
                'driver_id' => null,
                'vehicle_status_id' => 1, // Disponível
                'capacity' => 650.00,
                'description' => 'Van para cargas médias',
                'year' => 2022,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'license_plate_number' => 'GHI-9012',
                'model' => 'Mercedes Sprinter',
                'color' => 'Azul',
                'vehicle_type_id' => 4, // Caminhão 3/4
                'driver_id' => null,
                'vehicle_status_id' => 3, // Manutenção
                'capacity' => 3500.00,
                'description' => 'Caminhão para cargas pesadas',
                'year' => 2021,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Criar alguns produtos de exemplo
        DB::table('products')->insert([
            [
                'shop_id' => 1,
                'name' => 'Hambúrguer Artesanal',
                'description' => 'Hambúrguer com carne 180g, queijo, alface, tomate',
                'price' => 25.90,
                'product_category_id' => 1, // Alimentação
                'product_status_id' => 1, // Ativo
                'weight' => 0.35,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'shop_id' => 1,
                'name' => 'Refrigerante Lata',
                'description' => 'Refrigerante gelado 350ml',
                'price' => 4.50,
                'product_category_id' => 2, // Bebidas
                'product_status_id' => 1, // Ativo
                'weight' => 0.37,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'shop_id' => 2,
                'name' => 'Paracetamol 500mg',
                'description' => 'Caixa com 20 comprimidos',
                'price' => 8.90,
                'product_category_id' => 9, // Farmácia
                'product_status_id' => 1, // Ativo
                'weight' => 0.05,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        // Criar algumas localizações de exemplo
        DB::table('locations')->insert([
            [
                'name' => 'Centro da Cidade',
                'address' => 'Praça Central, s/n - Centro',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '01000-000',
                'latitude' => -23.5505,
                'longitude' => -46.6333,
                'reference' => 'Próximo à catedral',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Shopping Norte',
                'address' => 'Av. Norte, 1000 - Zona Norte',
                'city' => 'São Paulo',
                'state' => 'SP',
                'postal_code' => '02000-000',
                'latitude' => -23.5200,
                'longitude' => -46.6000,
                'reference' => 'Entrada principal do shopping',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
