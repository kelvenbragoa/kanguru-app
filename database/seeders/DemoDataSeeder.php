<?php

namespace Database\Seeders;

use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $this->_ensureLookupRows();

        $shops = [
            [
                'name' => 'Restaurante Sabor & Arte',
                'address' => 'Rua das Flores, 123 - Centro',
                'phone' => '(11) 99999-1234',
                'email' => 'contato@saborarte.com',
                'description' => 'Comida caseira e hambúrgueres artesanais',
                'delivery_fee' => '5.90',
                'review_count' => '128',
            ],
            [
                'name' => 'Farmácia São João',
                'address' => 'Av. Principal, 456 - Bairro Alto',
                'phone' => '(11) 99999-5678',
                'email' => 'farmacia@saojoao.com',
                'description' => 'Medicamentos e produtos de saúde',
                'delivery_fee' => '4.50',
                'review_count' => '64',
            ],
            [
                'name' => 'Supermercado Economia',
                'address' => 'Rua do Comércio, 789 - Vila Nova',
                'phone' => '(11) 99999-9012',
                'email' => 'vendas@economia.com',
                'description' => 'Mercearia e produtos do dia a dia',
                'delivery_fee' => '7.90',
                'review_count' => '89',
            ],
        ];

        foreach ($shops as $shop) {
            DB::table('shops')->updateOrInsert(
                ['email' => $shop['email']],
                array_merge($shop, ['updated_at' => $now, 'created_at' => $now])
            );
        }

        $shopIds = DB::table('shops')->pluck('id', 'email');

        $products = [
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Hambúrguer Artesanal',
                'description' => 'Hambúrguer com carne 180g, queijo, alface, tomate',
                'price' => 25.90,
                'product_category_id' => 1,
                'weight' => 0.35,
            ],
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Batata Frita',
                'description' => 'Porção média de batata frita crocante',
                'price' => 12.90,
                'product_category_id' => 1,
                'weight' => 0.25,
            ],
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Refrigerante Lata',
                'description' => 'Refrigerante gelado 350ml',
                'price' => 4.50,
                'product_category_id' => 2,
                'weight' => 0.37,
            ],
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Suco Natural',
                'description' => 'Suco de laranja ou maracujá 400ml',
                'price' => 8.90,
                'product_category_id' => 2,
                'weight' => 0.40,
            ],
            [
                'shop_email' => 'farmacia@saojoao.com',
                'name' => 'Paracetamol 500mg',
                'description' => 'Caixa com 20 comprimidos',
                'price' => 8.90,
                'product_category_id' => 9,
                'weight' => 0.05,
            ],
            [
                'shop_email' => 'farmacia@saojoao.com',
                'name' => 'Vitamina C',
                'description' => 'Frasco com 30 cápsulas',
                'price' => 19.90,
                'product_category_id' => 9,
                'weight' => 0.08,
            ],
            [
                'shop_email' => 'vendas@economia.com',
                'name' => 'Arroz 5kg',
                'description' => 'Arroz branco tipo 1',
                'price' => 32.90,
                'product_category_id' => 1,
                'weight' => 5.00,
            ],
            [
                'shop_email' => 'vendas@economia.com',
                'name' => 'Leite 1L',
                'description' => 'Leite integral UHT',
                'price' => 6.50,
                'product_category_id' => 2,
                'weight' => 1.00,
            ],
        ];

        foreach ($products as $product) {
            $shopId = $shopIds[$product['shop_email']] ?? null;
            if (! $shopId) {
                continue;
            }
            unset($product['shop_email']);
            DB::table('products')->updateOrInsert(
                ['shop_id' => $shopId, 'name' => $product['name']],
                array_merge($product, [
                    'shop_id' => $shopId,
                    'product_status_id' => 1,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }

        $vehicles = [
            [
                'license_plate_number' => 'ABC-1234',
                'model' => 'Honda CG 160',
                'color' => 'Vermelha',
                'vehicle_type_id' => 1,
                'driver_id' => DB::table('users')->where('email', 'motorista@kanguru.com')->value('id'),
                'vehicle_status_id' => 1,
                'capacity' => 30.00,
                'description' => 'Moto para delivery rápido',
                'year' => 2023,
            ],
            [
                'license_plate_number' => 'DEF-5678',
                'model' => 'Fiat Fiorino',
                'color' => 'Branca',
                'vehicle_type_id' => 3,
                'driver_id' => null,
                'vehicle_status_id' => 1,
                'capacity' => 650.00,
                'description' => 'Van para cargas médias',
                'year' => 2022,
            ],
        ];

        foreach ($vehicles as $vehicle) {
            DB::table('vehicles')->updateOrInsert(
                ['license_plate_number' => $vehicle['license_plate_number']],
                array_merge($vehicle, ['updated_at' => $now, 'created_at' => $now])
            );
        }

        $this->_seedCustomerAddresses();
    }

    private function _seedCustomerAddresses(): void
    {
        $customer = User::query()->where('email', 'cliente@kanguru.com')->first();
        if (! $customer || SavedAddress::query()->where('user_id', $customer->id)->exists()) {
            return;
        }

        SavedAddress::create([
            'user_id' => $customer->id,
            'label' => 'casa',
            'address' => 'Av. 24 de Julho, nº 450',
            'neighborhood' => 'Polana',
            'city' => 'Maputo',
            'reference' => 'Perto do Jardim dos Namorados',
            'is_default' => true,
        ]);

        SavedAddress::create([
            'user_id' => $customer->id,
            'label' => 'trabalho',
            'address' => 'Av. Julius Nyerere, nº 1128',
            'neighborhood' => 'Sommerschield',
            'city' => 'Maputo',
            'reference' => 'Edifício próximo à embaixada',
            'is_default' => false,
        ]);
    }

    private function _ensureLookupRows(): void
    {
        if (DB::table('product_categories')->count() === 0) {
            $this->call(ProductCategoryTableSeeder::class);
        }
        if (DB::table('product_statuses')->count() === 0) {
            $this->call(ProductStatusTableSeeder::class);
        }
        if (DB::table('order_types')->count() === 0) {
            $this->call(OrderTypeTableSeeder::class);
        }
        if (DB::table('order_statuses')->count() === 0) {
            $this->call(OrderStatusTableSeeder::class);
        }
        if (DB::table('vehicle_types')->count() === 0) {
            $this->call(VehicleTypeTableSeeder::class);
        }
        if (DB::table('vehicle_statuses')->count() === 0) {
            $this->call(VehicleStatusTableSeeder::class);
        }
    }
}
