<?php

namespace Database\Seeders;

use App\Models\SavedAddress;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $this->_ensureLookupRows();

        $ownerId = User::query()->where('email', 'parceiro@kanguru.com')->value('id');
        $driverId = User::query()->where('email', 'motorista@kanguru.com')->value('id');
        $fleetId = User::query()->where('email', 'frota@kanguru.com')->value('id');

        $shops = [
            [
                'name' => 'Restaurante Sabor & Arte',
                'type' => 'restaurant',
                'address' => 'Av. Samora Machel, Centro',
                'city' => 'Beira',
                'latitude' => -19.8333,
                'longitude' => 34.8500,
                'phone' => '843111111',
                'email' => 'contato@saborarte.com',
                'description' => 'Comida caseira e hambúrgueres artesanais',
                'delivery_fee' => '50',
                'commission_rate' => 0.15,
                'is_featured' => true,
                'is_paused' => false,
                'review_count' => '128',
                'owner_user_id' => $ownerId,
            ],
            [
                'name' => 'Farmácia São João',
                'type' => 'pharmacy',
                'address' => 'Av. das FPLM, Matacuane',
                'city' => 'Beira',
                'latitude' => -19.8349,
                'longitude' => 34.8612,
                'phone' => '843222222',
                'email' => 'farmacia@saojoao.com',
                'description' => 'Medicamentos e produtos de saúde',
                'delivery_fee' => '40',
                'commission_rate' => 0.12,
                'is_featured' => false,
                'is_paused' => false,
                'review_count' => '64',
                'owner_user_id' => $ownerId,
            ],
            [
                'name' => 'Supermercado Economia',
                'type' => 'market',
                'address' => 'Av. Julius Nyerere',
                'city' => 'Maputo',
                'latitude' => -25.9653,
                'longitude' => 32.5892,
                'phone' => '843333333',
                'email' => 'vendas@economia.com',
                'description' => 'Mercearia e produtos do dia a dia',
                'delivery_fee' => '60',
                'commission_rate' => 0.10,
                'is_featured' => false,
                'is_paused' => false,
                'review_count' => '89',
                'owner_user_id' => $ownerId,
            ],
            [
                'name' => 'Hotel Baía Beira',
                'type' => 'hotel',
                'address' => 'Av. Marginal, Macuti',
                'city' => 'Beira',
                'latitude' => -19.8430,
                'longitude' => 34.8700,
                'phone' => '843444444',
                'email' => 'reservas@hotelbaia.com',
                'description' => 'Quartos com vista para a baía',
                'delivery_fee' => '0',
                'commission_rate' => 0.12,
                'is_featured' => true,
                'is_paused' => false,
                'review_count' => '42',
                'owner_user_id' => $ownerId,
            ],
            [
                'name' => 'Kanguro Serviços Casa',
                'type' => 'services',
                'address' => 'Bairro Ponta Gea',
                'city' => 'Beira',
                'latitude' => -19.8448,
                'longitude' => 34.8456,
                'phone' => '843555555',
                'email' => 'servicos@kanguro.demo',
                'description' => 'Limpeza, electricista e canalização',
                'delivery_fee' => '0',
                'commission_rate' => 0.15,
                'is_featured' => false,
                'is_paused' => false,
                'review_count' => '21',
                'owner_user_id' => $ownerId,
            ],
        ];

        foreach ($shops as $shop) {
            $payload = array_merge($shop, ['updated_at' => $now, 'created_at' => $now]);
            // Só enviar colunas que existem (ambientes a meio do migrate)
            $payload = array_filter(
                $payload,
                fn ($_, $key) => Schema::hasColumn('shops', $key),
                ARRAY_FILTER_USE_BOTH
            );
            DB::table('shops')->updateOrInsert(['email' => $shop['email']], $payload);
        }

        $shopIds = DB::table('shops')->pluck('id', 'email');

        $products = [
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Hambúrguer Artesanal',
                'description' => 'Hambúrguer com carne 180g, queijo, alface, tomate',
                'price' => 250.00,
                'product_category_id' => 1,
                'weight' => 0.35,
                'stock_qty' => 40,
            ],
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Batata Frita',
                'description' => 'Porção média de batata frita crocante',
                'price' => 120.00,
                'product_category_id' => 1,
                'weight' => 0.25,
                'stock_qty' => 60,
            ],
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Refrigerante Lata',
                'description' => 'Refrigerante gelado 350ml',
                'price' => 45.00,
                'product_category_id' => 2,
                'weight' => 0.37,
                'stock_qty' => 100,
            ],
            [
                'shop_email' => 'contato@saborarte.com',
                'name' => 'Suco Natural',
                'description' => 'Suco de laranja ou maracujá 400ml',
                'price' => 80.00,
                'product_category_id' => 2,
                'weight' => 0.40,
                'stock_qty' => 50,
            ],
            [
                'shop_email' => 'farmacia@saojoao.com',
                'name' => 'Paracetamol 500mg',
                'description' => 'Caixa com 20 comprimidos',
                'price' => 90.00,
                'product_category_id' => 9,
                'weight' => 0.05,
                'stock_qty' => 80,
            ],
            [
                'shop_email' => 'farmacia@saojoao.com',
                'name' => 'Vitamina C',
                'description' => 'Frasco com 30 cápsulas',
                'price' => 190.00,
                'product_category_id' => 9,
                'weight' => 0.08,
                'stock_qty' => 35,
            ],
            [
                'shop_email' => 'vendas@economia.com',
                'name' => 'Arroz 5kg',
                'description' => 'Arroz branco tipo 1',
                'price' => 320.00,
                'product_category_id' => 1,
                'weight' => 5.00,
                'stock_qty' => 120,
            ],
            [
                'shop_email' => 'vendas@economia.com',
                'name' => 'Leite 1L',
                'description' => 'Leite integral UHT',
                'price' => 65.00,
                'product_category_id' => 2,
                'weight' => 1.00,
                'stock_qty' => 200,
            ],
        ];

        foreach ($products as $product) {
            $shopId = $shopIds[$product['shop_email']] ?? null;
            if (! $shopId) {
                continue;
            }
            unset($product['shop_email']);
            $row = array_merge($product, [
                'shop_id' => $shopId,
                'product_status_id' => 1,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            if (! Schema::hasColumn('products', 'stock_qty')) {
                unset($row['stock_qty']);
            }
            DB::table('products')->updateOrInsert(
                ['shop_id' => $shopId, 'name' => $product['name']],
                $row
            );
        }

        $this->_seedHotelRooms($shopIds['reservas@hotelbaia.com'] ?? null, $now);
        $this->_seedServices($shopIds['servicos@kanguro.demo'] ?? null, $now);

        $vehicles = [
            [
                'license_plate_number' => 'ABC-1234',
                'model' => 'Honda CG 160',
                'color' => 'Vermelha',
                'vehicle_type_id' => 1,
                'driver_id' => $driverId,
                'vehicle_status_id' => 1,
                'capacity' => 30.00,
                'description' => 'Moto delivery / táxi moto',
                'year' => 2023,
            ],
            [
                'license_plate_number' => 'DEF-5678',
                'model' => 'Toyota Corolla',
                'color' => 'Branca',
                'vehicle_type_id' => 2, // Carro
                'driver_id' => $fleetId,
                'vehicle_status_id' => 1,
                'capacity' => 400.00,
                'description' => 'Carro de frota Kanguro',
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

    private function _seedHotelRooms(?int $shopId, $now): void
    {
        if (! $shopId || ! Schema::hasTable('room_types')) {
            return;
        }

        $rooms = [
            [
                'name' => 'Standard',
                'description' => 'Quarto confortável para 2 pessoas',
                'capacity' => 2,
                'quantity' => 5,
                'price_per_night' => 2500,
                'deposit_percent' => 30,
                'free_cancel_days' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Suite Vista Mar',
                'description' => 'Suite com varanda e vista para a baía',
                'capacity' => 3,
                'quantity' => 2,
                'price_per_night' => 4500,
                'deposit_percent' => 30,
                'free_cancel_days' => 2,
                'is_active' => true,
            ],
        ];

        foreach ($rooms as $room) {
            $row = array_merge($room, [
                'shop_id' => $shopId,
                'updated_at' => $now,
                'created_at' => $now,
            ]);
            if (! Schema::hasColumn('room_types', 'deposit_percent')) {
                unset($row['deposit_percent']);
            }
            DB::table('room_types')->updateOrInsert(
                ['shop_id' => $shopId, 'name' => $room['name']],
                $row
            );
        }
    }

    private function _seedServices(?int $shopId, $now): void
    {
        if (! $shopId || ! Schema::hasTable('service_offerings')) {
            return;
        }

        $offerings = [
            [
                'title' => 'Limpeza doméstica (2h)',
                'category' => 'limpeza',
                'description' => 'Limpeza geral de apartamento ou casa',
                'price' => 800,
                'duration_minutes' => 120,
                'location_type' => 'onsite',
                'is_active' => true,
            ],
            [
                'title' => 'Electricista urgência',
                'category' => 'electricidade',
                'description' => 'Diagnóstico e reparação básica',
                'price' => 1200,
                'duration_minutes' => 90,
                'location_type' => 'onsite',
                'is_active' => true,
            ],
        ];

        foreach ($offerings as $offering) {
            DB::table('service_offerings')->updateOrInsert(
                ['shop_id' => $shopId, 'title' => $offering['title']],
                array_merge($offering, [
                    'shop_id' => $shopId,
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
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
