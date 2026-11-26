<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EventTicketTest extends TestCase
{
    use RefreshDatabase;

    public function test_events_catalog_proxies_mticket(): void
    {
        Http::fake([
            'backend.mticket.co.mz/api/eventos*' => Http::response([
                'provinces' => [],
                'categories' => [
                    ['id' => 1, 'name' => 'Música'],
                ],
                'events' => [
                    'current_page' => 1,
                    'per_page' => 12,
                    'total' => 1,
                    'last_page' => 1,
                    'data' => [
                        [
                            'id' => 10,
                            'slug' => 'festa-maputo',
                            'name' => 'Festa Maputo',
                            'address' => 'Maputo',
                            'start_date' => '2026-12-01',
                            'start_time' => '20:00:00',
                            'tickets_min_price' => 500,
                            'user' => ['name' => 'Promotor X', 'company_name' => 'Promotor X', 'slug' => 'promotor-x'],
                            'image' => 'events/festa.jpg',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->getJson('/api/v1/events')
            ->assertOk()
            ->assertJsonPath('data.events.0.title', 'Festa Maputo')
            ->assertJsonPath('data.categories.0.name', 'Música')
            ->assertJsonPath('data.mticket.android', 'https://play.google.com/store/apps/details?id=mz.co.mticket.client');
    }

    public function test_event_detail_includes_tickets_and_download_cta(): void
    {
        Http::fake([
            'backend.mticket.co.mz/api/eventos/festa-maputo' => Http::response([
                'events' => [
                    'id' => 10,
                    'slug' => 'festa-maputo',
                    'name' => 'Festa Maputo',
                    'description' => 'Noite grande',
                    'address' => 'Maputo',
                    'start_date' => '2026-12-01',
                    'start_time' => '20:00:00',
                    'user' => ['company_name' => 'Promotor X', 'slug' => 'promotor-x'],
                ],
                'recommended' => [],
            ], 200),
            'backend.mticket.co.mz/api/checkout/festa-maputo' => Http::response([
                'tickets' => [
                    [
                        'id' => 55,
                        'name' => 'Geral',
                        'description' => '',
                        'price' => 500,
                        'available_quantity' => 40,
                        'sale_status' => 'available',
                    ],
                ],
            ], 200),
        ]);

        $this->getJson('/api/v1/events/festa-maputo')
            ->assertOk()
            ->assertJsonPath('data.event.title', 'Festa Maputo')
            ->assertJsonPath('data.tickets.0.name', 'Geral')
            ->assertJsonPath('data.tickets.0.available_quantity', 40)
            ->assertJsonPath('data.mticket.event_url', 'https://mticket.co.mz/eventos/festa-maputo');
    }

    public function test_local_purchase_route_is_gone(): void
    {
        // Antiga rota de compra local já não existe (só GET no catálogo).
        $this->postJson('/api/v1/events/purchase', [])
            ->assertStatus(405);
    }
}
