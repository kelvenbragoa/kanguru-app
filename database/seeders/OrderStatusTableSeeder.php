<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OrderStatusTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('order_statuses')->insert([
            [
                'id' => 1,
                'name' => 'pending',
                'display_name' => 'Pendente',
                'description' => 'Pedido aguardando confirmação',
                'color' => '#6B7280',
                'order_sequence' => 1,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'confirmed',
                'display_name' => 'Confirmado',
                'description' => 'Pedido confirmado, aguardando motorista',
                'color' => '#3B82F6',
                'order_sequence' => 2,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'assigned',
                'display_name' => 'Designado',
                'description' => 'Motorista designado para o pedido',
                'color' => '#8B5CF6',
                'order_sequence' => 3,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'collecting',
                'display_name' => 'Coletando',
                'description' => 'Motorista a caminho para coleta',
                'color' => '#F59E0B',
                'order_sequence' => 4,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'collected',
                'display_name' => 'Coletado',
                'description' => 'Produto coletado',
                'color' => '#06B6D4',
                'order_sequence' => 5,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'in_transit',
                'display_name' => 'Em Trânsito',
                'description' => 'Produto em transporte',
                'color' => '#10B981',
                'order_sequence' => 6,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'delivering',
                'display_name' => 'Entregando',
                'description' => 'Motorista a caminho para entrega',
                'color' => '#84CC16',
                'order_sequence' => 7,
                'is_final' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'delivered',
                'display_name' => 'Entregue',
                'description' => 'Produto entregue com sucesso',
                'color' => '#22C55E',
                'order_sequence' => 8,
                'is_final' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'name' => 'cancelled',
                'display_name' => 'Cancelado',
                'description' => 'Pedido cancelado',
                'color' => '#EF4444',
                'order_sequence' => 99,
                'is_final' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'name' => 'failed',
                'display_name' => 'Falhou',
                'description' => 'Entrega não realizada',
                'color' => '#DC2626',
                'order_sequence' => 99,
                'is_final' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
