<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MTicketClient;
use Illuminate\Http\Request;

/**
 * Catálogo de eventos via mTicket. Kanguro não vende bilhetes.
 */
class EventController extends Controller
{
    public function __construct(private MTicketClient $mticket)
    {
    }

    public function index(Request $request)
    {
        try {
            $query = array_filter([
                'search' => $request->input('search'),
                'province_id' => $request->input('province_id'),
                'category_id' => $request->input('category_id'),
                'page' => $request->input('page', 1),
                'per_page' => $this->clampPerPage($request->integer('per_page', 12)),
            ], fn ($v) => $v !== null && $v !== '');

            $raw = $this->mticket->get('/eventos', $query);
            $paginator = $raw['events'] ?? [];
            $items = collect($paginator['data'] ?? [])->map(fn ($e) => $this->mapEventCard($e))->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'events' => $items,
                    'categories' => collect($raw['categories'] ?? [])->map(fn ($c) => $this->mapCategory($c))->values(),
                    'provinces' => $raw['provinces'] ?? [],
                    'pagination' => [
                        'current_page' => (int) ($paginator['current_page'] ?? 1),
                        'per_page' => (int) ($paginator['per_page'] ?? 12),
                        'total' => (int) ($paginator['total'] ?? $items->count()),
                        'last_page' => (int) ($paginator['last_page'] ?? 1),
                    ],
                    'mticket' => $this->mticket->downloadLinks(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function show(string $id)
    {
        try {
            $raw = $this->mticket->get('/eventos/'.$id);
            $event = $raw['events'] ?? null;
            if (! $event) {
                return response()->json(['status' => 'error', 'message' => 'Evento não encontrado'], 404);
            }

            $slugOrId = $event['slug'] ?? $event['id'] ?? $id;
            $checkout = [];
            try {
                $checkout = $this->mticket->get('/checkout/'.$slugOrId);
            } catch (\Throwable) {
                $checkout = [];
            }

            $tickets = collect($checkout['tickets'] ?? [])->map(fn ($t) => $this->mapTicket($t))->values();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'event' => $this->mapEventDetail($event),
                    'tickets' => $tickets,
                    'recommended' => collect($raw['recommended'] ?? [])->map(fn ($e) => $this->mapEventCard($e))->values(),
                    'mticket' => array_merge(
                        $this->mticket->downloadLinks(),
                        ['event_url' => $this->mticket->eventDeepLink($slugOrId)]
                    ),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function categories()
    {
        try {
            $raw = $this->mticket->getClient('/categories');
            $list = $raw['data'] ?? $raw;

            if (! is_array($list)) {
                $list = [];
            }

            // client envelope: data may be list directly
            if (isset($list[0]) || $list === []) {
                $categories = $list;
            } else {
                $categories = $list['categories'] ?? array_values($list);
            }

            return response()->json([
                'status' => 'success',
                'data' => collect($categories)->map(fn ($c) => $this->mapCategory($c))->values(),
            ]);
        } catch (\Throwable $e) {
            // fallback: categories from eventos payload
            try {
                $raw = $this->mticket->get('/eventos', ['per_page' => 9]);
                return response()->json([
                    'status' => 'success',
                    'data' => collect($raw['categories'] ?? [])->map(fn ($c) => $this->mapCategory($c))->values(),
                ]);
            } catch (\Throwable $inner) {
                return $this->fail($inner);
            }
        }
    }

    public function promoters(Request $request)
    {
        try {
            $query = array_filter([
                'search' => $request->input('search'),
                'page' => $request->input('page', 1),
                'per_page' => $this->clampPerPage($request->integer('per_page', 12)),
            ], fn ($v) => $v !== null && $v !== '');

            $raw = $this->mticket->get('/promotores', $query);
            $paginator = $raw['promotores'] ?? $raw['promoters'] ?? $raw;
            $items = is_array($paginator) && isset($paginator['data'])
                ? $paginator['data']
                : (is_array($paginator) ? $paginator : []);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'promoters' => collect($items)->map(fn ($p) => $this->mapPromoter($p))->values(),
                    'pagination' => [
                        'current_page' => (int) ($paginator['current_page'] ?? 1),
                        'per_page' => (int) ($paginator['per_page'] ?? 12),
                        'total' => (int) ($paginator['total'] ?? count($items)),
                        'last_page' => (int) ($paginator['last_page'] ?? 1),
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function showPromoter(string $slug)
    {
        try {
            $raw = $this->mticket->get('/promotores/'.$slug, [
                'page' => request('page', 1),
                'per_page' => $this->clampPerPage((int) request('per_page', 12)),
            ]);

            $promoter = $raw['promotor'] ?? $raw['promoter'] ?? $raw['user'] ?? null;
            $events = $raw['events']['data'] ?? $raw['events'] ?? [];

            if (! $promoter) {
                return response()->json(['status' => 'error', 'message' => 'Promotor não encontrado'], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => [
                    'promoter' => $this->mapPromoter($promoter),
                    'events' => collect(is_array($events) ? $events : [])->map(fn ($e) => $this->mapEventCard($e))->values(),
                    'mticket' => $this->mticket->downloadLinks(),
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->fail($e);
        }
    }

    public function downloadInfo()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->mticket->downloadLinks(),
        ]);
    }

    private function mapEventCard(array $e): array
    {
        $user = is_array($e['user'] ?? null) ? $e['user'] : [];
        $category = is_array($e['category'] ?? null) ? $e['category'] : (is_array($e['type'] ?? null) ? $e['type'] : []);
        $min = $e['tickets_min_price'] ?? $e['price'] ?? ($e['price_range']['min'] ?? null);
        $start = $this->combineDateTime($e['start_date'] ?? null, $e['start_time'] ?? null)
            ?? ($e['date_time'] ?? $e['starts_at'] ?? null);

        return [
            'id' => $e['id'] ?? null,
            'slug' => $e['slug'] ?? null,
            'title' => $e['name'] ?? $e['title'] ?? 'Evento',
            'image' => $this->mticket->storageUrl($e['image'] ?? $e['image_url'] ?? null),
            'category' => $category['name'] ?? ($e['category'] ?? null),
            'category_id' => $e['main_category_id'] ?? $category['id'] ?? null,
            'venue' => $e['address'] ?? ($e['venue']['name'] ?? $e['venue'] ?? null),
            'city' => is_array($e['city'] ?? null) ? ($e['city']['name'] ?? null) : ($e['city'] ?? null),
            'province' => is_array($e['province'] ?? null) ? ($e['province']['name'] ?? null) : ($e['province'] ?? null),
            'starts_at' => $start,
            'min_price' => $min !== null ? (float) $min : null,
            'organizer_name' => $user['company_name'] ?? $user['name'] ?? $e['organizer_name'] ?? 'Promotor',
            'organizer_slug' => $user['slug'] ?? $e['organizer_slug'] ?? null,
            'is_sales_closed' => (bool) ($e['is_sales_closed'] ?? false),
            'mticket_url' => $this->mticket->eventDeepLink($e['slug'] ?? $e['id'] ?? ''),
        ];
    }

    private function mapEventDetail(array $e): array
    {
        $card = $this->mapEventCard($e);
        $card['description'] = $e['description'] ?? $e['short_description'] ?? '';
        $card['end_at'] = $this->combineDateTime($e['end_date'] ?? null, $e['end_time'] ?? null)
            ?? ($e['end_date_time'] ?? null);
        $card['latitude'] = $e['latitude'] ?? ($e['venue']['latitude'] ?? null);
        $card['longitude'] = $e['longitude'] ?? ($e['venue']['longitude'] ?? null);

        return $card;
    }

    private function mapTicket(array $t): array
    {
        $available = $t['available_quantity'] ?? null;

        return [
            'id' => $t['id'] ?? null,
            'name' => $t['name'] ?? 'Bilhete',
            'description' => $t['description'] ?? '',
            'price' => (float) ($t['price'] ?? 0),
            'available_quantity' => $available !== null ? (int) $available : null,
            'max_per_order' => isset($t['max_per_order']) ? (int) $t['max_per_order'] : null,
            'sale_status' => $t['sale_status'] ?? ($available !== null && (int) $available <= 0 ? 'sold_out' : 'available'),
            'is_on_sale' => ($t['sale_status'] ?? 'available') === 'available'
                || (($available ?? 1) > 0 && empty($t['sale_status'])),
        ];
    }

    private function mapCategory(array $c): array
    {
        return [
            'id' => $c['id'] ?? null,
            'name' => $c['name'] ?? 'Categoria',
            'image' => $this->mticket->storageUrl($c['image'] ?? $c['image_url'] ?? null),
            'events_count' => isset($c['events_count']) ? (int) $c['events_count'] : null,
        ];
    }

    private function mapPromoter(array $p): array
    {
        return [
            'id' => $p['id'] ?? null,
            'name' => $p['company_name'] ?? $p['name'] ?? 'Promotor',
            'slug' => $p['slug'] ?? null,
            'description' => $p['description'] ?? null,
            'image' => $this->mticket->storageUrl($p['image'] ?? null),
            'banner' => $this->mticket->storageUrl($p['banner'] ?? null),
            'location' => $p['company_location'] ?? null,
            'events_count' => isset($p['events_count']) ? (int) $p['events_count'] : null,
        ];
    }

    private function combineDateTime(?string $date, ?string $time): ?string
    {
        if (! $date) {
            return null;
        }
        $time = $time ?: '00:00:00';
        if (strlen($time) === 5) {
            $time .= ':00';
        }

        return $date.' '.$time;
    }

    private function clampPerPage(int $perPage): int
    {
        return in_array($perPage, [9, 12, 24], true) ? $perPage : 12;
    }

    private function fail(\Throwable $e)
    {
        $status = $e->getCode();
        if (! is_int($status) || $status < 400 || $status > 599) {
            $status = 502;
        }

        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage() ?: 'Erro ao contactar o mTicket',
        ], $status);
    }
}
