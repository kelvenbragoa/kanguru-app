<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\HotelBooking;
use App\Models\Order;
use App\Models\OrderException;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\RoomType;
use App\Models\ServiceBooking;
use App\Models\ServiceOffering;
use App\Models\Shop;
use App\Models\StockMovement;
use App\Models\TicketLot;
use App\Models\TrackingOrder;
use App\Services\DispatchService;
use App\Services\ProductStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BusinessController extends Controller
{
    public function __construct(
        private DispatchService $dispatch,
        private ProductStockService $stock,
    ) {
    }

    public function dashboard(Request $request)
    {
        $shops = $this->ownedShops($request->user());
        $shopIds = $shops->pluck('id');

        $pending = Order::query()
            ->whereIn('shop_id', $shopIds)
            ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['pending', 'confirmed']))
            ->count();

        $preparing = Order::query()
            ->whereIn('shop_id', $shopIds)
            ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['preparing', 'ready']))
            ->count();

        $todayDelivered = Order::query()
            ->whereIn('shop_id', $shopIds)
            ->whereHas('orderStatus', fn ($q) => $q->where('name', 'delivered'))
            ->whereDate('delivered_at', today())
            ->count();

        $lowStock = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->whereNotNull('stock_qty')
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->count();

        $hotelShopIds = $shops->where('type', Shop::TYPE_HOTEL)->pluck('id');
        $upcomingBookings = HotelBooking::query()
            ->whereIn('shop_id', $hotelShopIds)
            ->whereIn('status', [HotelBooking::STATUS_PENDING, HotelBooking::STATUS_CONFIRMED])
            ->whereDate('check_in', '>=', today())
            ->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'shops' => $shops,
                'pending_orders' => $pending,
                'preparing_orders' => $preparing,
                'delivered_today' => $todayDelivered,
                'low_stock_products' => $lowStock,
                'upcoming_bookings' => $upcomingBookings,
            ],
        ]);
    }

    public function orders(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');

        $query = Order::query()
            ->whereIn('shop_id', $shopIds)
            ->with(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments', 'agent']);

        if ($request->filled('status')) {
            $query->whereHas('orderStatus', fn ($q) => $q->where('name', $request->status));
        }

        if ($request->boolean('inbox')) {
            $query->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['pending', 'confirmed', 'preparing', 'ready']));
        }

        $orders = $query->orderByDesc('created_at')->paginate(
            min(max($request->integer('per_page', 20), 1), 50)
        );

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    public function showOrder(Request $request, string $id)
    {
        $order = $this->findOwnedOrder($request->user(), $id);

        return response()->json([
            'status' => 'success',
            'data' => $order->load([
                'user', 'shop', 'orderType', 'orderStatus',
                'orderItems.product', 'payments', 'agent', 'trackingOrders.orderStatus',
            ]),
        ]);
    }

    public function products(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');

        $query = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->with(['shop', 'productCategory', 'productStatus']);

        if ($request->filled('shop_id')) {
            $query->where('shop_id', $request->shop_id);
        }

        $products = $query->orderBy('name')->paginate(
            min(max($request->integer('per_page', 30), 1), 100)
        );

        return response()->json([
            'status' => 'success',
            'data' => $products,
        ]);
    }

    public function storeProduct(Request $request)
    {
        $shop = $this->resolveOwnedShop($request->user(), $request->integer('shop_id'));

        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'product_category_id' => 'required|exists:product_categories,id',
            'product_status_id' => 'required|exists:product_statuses,id',
            'weight' => 'nullable|numeric|min:0',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::create([
            ...$request->only([
                'name', 'description', 'price', 'product_category_id',
                'product_status_id', 'weight', 'stock_qty', 'low_stock_threshold', 'image',
            ]),
            'shop_id' => $shop->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Produto criado',
            'data' => $product->load(['shop', 'productCategory', 'productStatus']),
        ], 201);
    }

    public function updateProduct(Request $request, string $id)
    {
        $product = $this->findOwnedProduct($request->user(), $id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'product_category_id' => 'sometimes|required|exists:product_categories,id',
            'product_status_id' => 'sometimes|required|exists:product_statuses,id',
            'weight' => 'nullable|numeric|min:0',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'image' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product->update($request->only([
            'name', 'description', 'price', 'product_category_id',
            'product_status_id', 'weight', 'stock_qty', 'low_stock_threshold', 'image',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Produto actualizado',
            'data' => $product->fresh(['shop', 'productCategory', 'productStatus']),
        ]);
    }

    public function markSoldOut(Request $request, string $id)
    {
        $product = $this->findOwnedProduct($request->user(), $id);
        $soldOutId = ProductStatus::query()->where('name', 'Esgotado')->value('id');

        $product->update([
            'stock_qty' => 0,
            'product_status_id' => $soldOutId ?? $product->product_status_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Produto marcado como esgotado',
            'data' => $product->fresh(['shop', 'productCategory', 'productStatus']),
        ]);
    }

    public function exportProductsCsv(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');
        $products = Product::query()
            ->whereIn('shop_id', $shopIds)
            ->when($request->filled('shop_id'), fn ($q) => $q->where('shop_id', $request->shop_id))
            ->orderBy('name')
            ->get();

        $lines = ["id,shop_id,name,price,stock_qty,product_category_id,product_status_id"];
        foreach ($products as $p) {
            $lines[] = implode(',', [
                $p->id,
                $p->shop_id,
                '"'.str_replace('"', '""', $p->name).'"',
                $p->price,
                (int) $p->stock_qty,
                $p->product_category_id,
                $p->product_status_id,
            ]);
        }

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="produtos.csv"',
        ]);
    }

    public function importProductsCsv(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'csv' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $shop = $this->resolveOwnedShop($request->user(), (int) $request->shop_id);
        $rows = preg_split('/\r\n|\r|\n/', trim($request->csv)) ?: [];
        $updated = 0;
        $created = 0;

        foreach ($rows as $i => $line) {
            if ($i === 0 && str_contains(strtolower($line), 'name')) {
                continue;
            }
            if (trim($line) === '') {
                continue;
            }
            $cols = str_getcsv($line);
            if (count($cols) < 4) {
                continue;
            }

            // id,shop_id,name,price,stock_qty,product_category_id,product_status_id
            $id = is_numeric($cols[0] ?? null) ? (int) $cols[0] : null;
            $name = trim((string) ($cols[2] ?? $cols[0]));
            $price = (float) ($cols[3] ?? $cols[1] ?? 0);
            $stock = (int) ($cols[4] ?? $cols[2] ?? 0);
            $categoryId = (int) ($cols[5] ?? 0) ?: null;
            $statusId = (int) ($cols[6] ?? 0) ?: null;

            if ($name === '') {
                continue;
            }

            if ($id) {
                $product = Product::query()->where('id', $id)->where('shop_id', $shop->id)->first();
                if ($product) {
                    $product->update([
                        'name' => $name,
                        'price' => $price,
                        'stock_qty' => $stock,
                        'product_category_id' => $categoryId ?: $product->product_category_id,
                        'product_status_id' => $statusId ?: $product->product_status_id,
                    ]);
                    $updated++;
                    continue;
                }
            }

            if (! $categoryId || ! $statusId) {
                continue;
            }

            Product::create([
                'shop_id' => $shop->id,
                'name' => $name,
                'price' => $price,
                'stock_qty' => $stock,
                'product_category_id' => $categoryId,
                'product_status_id' => $statusId,
            ]);
            $created++;
        }

        return response()->json([
            'status' => 'success',
            'message' => "Importação: {$created} criados, {$updated} actualizados",
            'data' => compact('created', 'updated'),
        ]);
    }

    public function productMovements(Request $request, string $id)
    {
        $product = $this->findOwnedProduct($request->user(), $id);
        $movements = StockMovement::query()
            ->where('product_id', $product->id)
            ->orderByDesc('created_at')
            ->paginate(min(max($request->integer('per_page', 30), 1), 100));

        return response()->json(['status' => 'success', 'data' => $movements]);
    }

    public function requestFeatured(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'subscription_plan' => 'nullable|string|in:basic,featured,premium',
            'want_featured' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $shop = $this->resolveOwnedShop($request->user(), (int) $request->shop_id);
        $plan = $request->boolean('want_featured')
            ? 'featured_pending'
            : ($request->input('subscription_plan') ?: ($shop->subscription_plan ?: 'basic'));
        $shop->update(['subscription_plan' => $plan]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido de plano/destaque registado — aguarda aprovação Kanguro',
            'data' => $shop->fresh(),
        ]);
    }

    public function roomTypes(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');

        $rooms = RoomType::query()
            ->whereIn('shop_id', $shopIds)
            ->with('shop')
            ->orderBy('name')
            ->get();

        return response()->json(['status' => 'success', 'data' => $rooms]);
    }

    public function storeRoomType(Request $request)
    {
        $shop = $this->resolveOwnedShop($request->user(), $request->integer('shop_id'));
        abort_unless($shop->type === Shop::TYPE_HOTEL, 422, 'Só hotéis podem ter quartos');

        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'required|integer|min:1|max:20',
            'quantity' => 'required|integer|min:1|max:500',
            'price_per_night' => 'required|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'free_cancel_days' => 'nullable|integer|min:0|max:60',
            'deposit_percent' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $room = RoomType::create([
            ...$request->only([
                'name', 'description', 'capacity', 'quantity', 'price_per_night',
                'image', 'is_active', 'free_cancel_days', 'deposit_percent',
            ]),
            'shop_id' => $shop->id,
            'is_active' => $request->boolean('is_active', true),
            'free_cancel_days' => $request->integer('free_cancel_days', 1),
            'deposit_percent' => $request->integer('deposit_percent', 30),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de quarto criado',
            'data' => $room->load('shop'),
        ], 201);
    }

    public function updateRoomType(Request $request, string $id)
    {
        $room = RoomType::with('shop')->findOrFail($id);
        abort_unless(
            $room->shop && (int) $room->shop->owner_user_id === (int) $request->user()->id,
            403,
            'Unauthorized'
        );

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'capacity' => 'sometimes|required|integer|min:1|max:20',
            'quantity' => 'sometimes|required|integer|min:1|max:500',
            'price_per_night' => 'sometimes|required|numeric|min:0',
            'image' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
            'free_cancel_days' => 'nullable|integer|min:0|max:60',
            'deposit_percent' => 'nullable|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $room->update($request->only([
            'name', 'description', 'capacity', 'quantity', 'price_per_night',
            'image', 'is_active', 'free_cancel_days', 'deposit_percent',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Tipo de quarto actualizado',
            'data' => $room->fresh('shop'),
        ]);
    }

    public function hotelBookings(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())
            ->where('type', Shop::TYPE_HOTEL)
            ->pluck('id');

        $query = HotelBooking::query()
            ->whereIn('shop_id', $shopIds)
            ->with(['user', 'shop', 'roomType']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('check_in')->paginate(
            min(max($request->integer('per_page', 20), 1), 50)
        );

        return response()->json(['status' => 'success', 'data' => $bookings]);
    }

    public function events(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');

        $events = Event::query()
            ->whereIn('shop_id', $shopIds)
            ->with(['shop', 'lots'])
            ->orderByDesc('starts_at')
            ->get();

        return response()->json(['status' => 'success', 'data' => $events]);
    }

    public function storeEvent(Request $request)
    {
        $shop = $this->resolveOwnedShop($request->user(), $request->integer('shop_id'));
        abort_unless($shop->type === Shop::TYPE_EVENTS, 422, 'Só organizadores (tipo Eventos) podem criar eventos');

        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:64',
            'venue' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'poster' => 'nullable|string|max:500',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = Event::create([
            ...$request->only([
                'title', 'category', 'venue', 'description', 'poster', 'starts_at', 'ends_at', 'is_active',
            ]),
            'shop_id' => $shop->id,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Evento criado',
            'data' => $event->load(['shop', 'lots']),
        ], 201);
    }

    public function updateEvent(Request $request, string $id)
    {
        $event = Event::with('shop')->findOrFail($id);
        abort_unless(
            $event->shop && (int) $event->shop->owner_user_id === (int) $request->user()->id,
            403,
            'Unauthorized'
        );

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:64',
            'venue' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'poster' => 'nullable|string|max:500',
            'starts_at' => 'sometimes|required|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event->update($request->only([
            'title', 'category', 'venue', 'description', 'poster', 'starts_at', 'ends_at', 'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Evento actualizado',
            'data' => $event->fresh(['shop', 'lots']),
        ]);
    }

    public function storeLot(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|exists:events,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1|max:100000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $event = Event::with('shop')->findOrFail($request->event_id);
        abort_unless(
            $event->shop && (int) $event->shop->owner_user_id === (int) $request->user()->id,
            403,
            'Unauthorized'
        );

        $lot = TicketLot::create([
            ...$request->only(['name', 'description', 'price', 'quantity', 'is_active']),
            'event_id' => $event->id,
            'is_active' => $request->boolean('is_active', true),
            'sold' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Lote criado',
            'data' => $lot,
        ], 201);
    }

    public function updateLot(Request $request, string $id)
    {
        $lot = TicketLot::with('event.shop')->findOrFail($id);
        abort_unless(
            $lot->event?->shop && (int) $lot->event->shop->owner_user_id === (int) $request->user()->id,
            403,
            'Unauthorized'
        );

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'quantity' => 'sometimes|required|integer|min:1|max:100000',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->only(['name', 'description', 'price', 'quantity', 'is_active']);
        if (isset($data['quantity']) && (int) $data['quantity'] < (int) $lot->sold) {
            return response()->json([
                'status' => 'error',
                'message' => 'Quantidade não pode ser inferior aos bilhetes já vendidos',
            ], 422);
        }

        $lot->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Lote actualizado',
            'data' => $lot->fresh(),
        ]);
    }

    public function rejectOrder(Request $request, string $id)
    {
        $user = $request->user();
        $reason = (string) $request->input('reason', 'Parceiro recusou o pedido');

        return DB::transaction(function () use ($id, $user, $reason) {
            $order = Order::with(['orderStatus', 'shop'])->lockForUpdate()->findOrFail($id);
            $this->assertOwnsOrder($user, $order);

            if (! in_array($order->orderStatus->name, ['pending', 'confirmed'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Só é possível rejeitar pedidos novos',
                ], 422);
            }

            $cancelledId = Order::statusId('cancelled') ?? 9;
            $order->update(['order_status_id' => $cancelledId]);
            $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
            $order->payments()->where('status', 'completed')->update(['status' => 'refunded']);

            TrackingOrder::create([
                'order_id' => $order->id,
                'order_status_id' => $cancelledId,
                'description' => $reason,
                'local' => $order->origin,
                'updated_by' => $user->id,
            ]);

            OrderException::create([
                'order_id' => $order->id,
                'type' => 'partner_refused',
                'description' => $reason,
                'cost_bearer' => OrderException::defaultBearer('partner_refused'),
                'status' => OrderException::STATUS_OPEN,
                'reported_by' => $user->id,
            ]);

            $this->stock->releaseForOrder($order);
            $this->dispatch->notifyOrderCancelled($order->fresh(['agent', 'user', 'orderStatus']));

            if ($order->user) {
                $this->dispatch->notifyStatusChange(
                    $order->fresh(['user', 'orderStatus']),
                    'Cancelado pelo parceiro'
                );
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pedido rejeitado',
                'data' => $order->fresh([
                    'user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments',
                ]),
            ]);
        });
    }

    public function serviceOfferings(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');

        $items = ServiceOffering::query()
            ->whereIn('shop_id', $shopIds)
            ->with('shop:id,name,type')
            ->orderBy('title')
            ->get();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function storeServiceOffering(Request $request)
    {
        $shop = $this->resolveOwnedShop($request->user(), $request->integer('shop_id'));
        abort_unless($shop->type === Shop::TYPE_SERVICES, 422, 'Só prestadores (tipo Serviços) podem criar ofertas');

        $validator = Validator::make($request->all(), [
            'shop_id' => 'required|exists:shops,id',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:64',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:15|max:1440',
            'location_type' => 'nullable|string|in:onsite,remote,both',
            'image' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $offering = ServiceOffering::create([
            ...$request->only([
                'title', 'category', 'description', 'price', 'duration_minutes',
                'location_type', 'image', 'is_active',
            ]),
            'shop_id' => $shop->id,
            'duration_minutes' => $request->integer('duration_minutes', 60),
            'location_type' => $request->input('location_type', ServiceOffering::LOCATION_ONSITE),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Serviço criado',
            'data' => $offering->load('shop'),
        ], 201);
    }

    public function updateServiceOffering(Request $request, string $id)
    {
        $offering = ServiceOffering::with('shop')->findOrFail($id);
        abort_unless(
            $offering->shop && (int) $offering->shop->owner_user_id === (int) $request->user()->id,
            403,
            'Unauthorized'
        );

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'category' => 'nullable|string|max:64',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:15|max:1440',
            'location_type' => 'nullable|string|in:onsite,remote,both',
            'image' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $offering->update($request->only([
            'title', 'category', 'description', 'price', 'duration_minutes',
            'location_type', 'image', 'is_active',
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Serviço actualizado',
            'data' => $offering->fresh('shop'),
        ]);
    }

    public function serviceBookings(Request $request)
    {
        $shopIds = $this->ownedShops($request->user())->pluck('id');

        $query = ServiceBooking::query()
            ->whereIn('shop_id', $shopIds)
            ->with(['user:id,name', 'shop:id,name', 'offering']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $bookings = $query->orderByDesc('scheduled_at')->paginate(
            min(max($request->integer('per_page', 20), 1), 50)
        );

        return response()->json(['status' => 'success', 'data' => $bookings]);
    }

    private function ownedShops($user)
    {
        return Shop::query()
            ->where('owner_user_id', $user->id)
            ->orderBy('name')
            ->get();
    }

    private function resolveOwnedShop($user, ?int $shopId): Shop
    {
        $query = Shop::query()->where('owner_user_id', $user->id);
        if ($shopId) {
            $query->where('id', $shopId);
        }

        $shop = $query->first();
        abort_unless($shop, 403, 'Sem loja associada');

        return $shop;
    }

    private function findOwnedOrder($user, string $id): Order
    {
        $order = Order::findOrFail($id);
        $this->assertOwnsOrder($user, $order);

        return $order;
    }

    private function findOwnedProduct($user, string $id): Product
    {
        $product = Product::with('shop')->findOrFail($id);
        abort_unless(
            $product->shop && (int) $product->shop->owner_user_id === (int) $user->id,
            403,
            'Unauthorized'
        );

        return $product;
    }

    private function assertOwnsOrder($user, Order $order): void
    {
        abort_unless($order->shop_id, 403, 'Unauthorized');
        $owns = Shop::query()
            ->where('id', $order->shop_id)
            ->where('owner_user_id', $user->id)
            ->exists();
        abort_unless($owns, 403, 'Unauthorized');
    }
}
