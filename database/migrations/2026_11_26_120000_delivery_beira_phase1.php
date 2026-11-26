<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_code', 4)->nullable()->after('code');
            $table->unsignedInteger('preparation_minutes')->nullable()->after('notes');
            $table->timestamp('courier_call_at')->nullable()->after('preparation_minutes');
            $table->timestamp('shop_accepted_at')->nullable()->after('courier_call_at');
            $table->timestamp('ready_at')->nullable()->after('shop_accepted_at');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_qty')->nullable()->after('weight');
            $table->unsignedInteger('low_stock_threshold')->default(5)->after('stock_qty');
        });

        $now = now();
        // Só inserir se a tabela já tiver os estados base (produção). Em testes
        // o seeder corre depois e inclui preparing/ready com IDs fixos 11/12.
        if (DB::table('order_statuses')->where('name', 'pending')->exists()) {
            $existing = DB::table('order_statuses')->whereIn('name', ['preparing', 'ready'])->pluck('name')->all();

            if (! in_array('preparing', $existing, true)) {
                DB::table('order_statuses')->insert([
                    'id' => 11,
                    'name' => 'preparing',
                    'display_name' => 'Em preparação',
                    'description' => 'Parceiro a preparar o pedido',
                    'color' => '#F59E0B',
                    'order_sequence' => 25,
                    'is_final' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if (! in_array('ready', $existing, true)) {
                DB::table('order_statuses')->insert([
                    'id' => 12,
                    'name' => 'ready',
                    'display_name' => 'Pronto para recolha',
                    'description' => 'Pedido pronto no balcão',
                    'color' => '#0EA5E9',
                    'order_sequence' => 26,
                    'is_final' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('order_statuses')->where('name', 'pending')->update([
                'display_name' => 'Enviado ao parceiro',
                'description' => 'Pedido pago/registado, à espera da aceitação',
            ]);
            DB::table('order_statuses')->where('name', 'confirmed')->update([
                'display_name' => 'Aceite',
                'description' => 'Pedido aceite, em fila de despacho',
            ]);
            DB::table('order_statuses')->where('name', 'assigned')->update([
                'display_name' => 'Estafeta atribuído',
            ]);
            DB::table('order_statuses')->where('name', 'collecting')->update([
                'display_name' => 'A caminho do parceiro',
            ]);
            DB::table('order_statuses')->where('name', 'collected')->update([
                'display_name' => 'A caminho do cliente',
            ]);
            DB::table('order_statuses')->where('name', 'in_transit')->update([
                'display_name' => 'A caminho do cliente',
            ]);
            DB::table('order_statuses')->where('name', 'delivering')->update([
                'display_name' => 'A entregar',
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_code',
                'preparation_minutes',
                'courier_call_at',
                'shop_accepted_at',
                'ready_at',
            ]);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_qty', 'low_stock_threshold']);
        });

        DB::table('order_statuses')->whereIn('name', ['preparing', 'ready'])->delete();
    }
};
