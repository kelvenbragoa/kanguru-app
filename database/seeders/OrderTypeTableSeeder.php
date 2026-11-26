<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderTypeTableSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['id' => 1, 'name' => 'Delivery'],
            ['id' => 2, 'name' => 'Transporte de Carga'],
            ['id' => 3, 'name' => 'Mudança'],
            ['id' => 4, 'name' => 'Coleta e Entrega'],
            ['id' => 5, 'name' => 'Express'],
            ['id' => 6, 'name' => 'Táxi'],
        ];

        $now = now();
        foreach ($rows as $row) {
            DB::table('order_types')->updateOrInsert(
                ['id' => $row['id']],
                array_merge($row, [
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
        }
    }
}
