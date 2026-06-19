<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VehicleStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('vehicle_statuses')->insert([
            [
                'id' => 1,
                'name' => 'available',
                'display_name' => 'Disponível',
                'description' => 'Veículo disponível para novas entregas',
                'color' => '#10B981',
                'is_available' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'busy',
                'display_name' => 'Ocupado',
                'description' => 'Veículo realizando entrega',
                'color' => '#F59E0B',
                'is_available' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'maintenance',
                'display_name' => 'Manutenção',
                'description' => 'Veículo em manutenção',
                'color' => '#EF4444',
                'is_available' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'inactive',
                'display_name' => 'Inativo',
                'description' => 'Veículo temporariamente inativo',
                'color' => '#6B7280',
                'is_available' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
