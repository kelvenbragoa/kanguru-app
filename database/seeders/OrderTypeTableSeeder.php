<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('order_types')->insert([
            [
                'id' => 1,
                'name' => 'Delivery',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Transporte de Carga',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Mudança',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Coleta e Entrega',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Express',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        if (! DB::table('order_types')->where('name', 'Táxi')->exists()) {
            DB::table('order_types')->insert([
                'name' => 'Táxi',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
