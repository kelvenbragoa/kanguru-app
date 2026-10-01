<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\TrackingOrder;
use App\Services\DispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(private DispatchService $dispatch)
    {
    }

    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $user?->loadMissing('role');
        $query = Order::with(['user', 'agent.profile', 'shop', 'vehicle', 'orderType', 'orderStatus', 'orderItems.product', 'payments']);

        // Filtrar por role do usuário
        if ($user?->role?->name === 'customer') {
            $query->where('user_id', $user->id);
        } elseif ($user?->role?->name === 'driver') {
            $query->where('agent_user_id', $user->id);
        }

        // Filtros opcionais
        if ($request->filled('status')) {
            $query->whereHas('orderStatus', function ($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        if ($request->filled('type')) {
            $query->whereHas('orderType', function ($q) use ($request) {
                $q->where('name', $request->type);
            });
        }

        if ($request->filled('search')) {
            $search = $request->string('search')->toString();
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('origin', 'like', '%'.$search.'%')
                    ->orWhere('destination', 'like', '%'.$search.'%')
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->boolean('unassigned')) {
            $query->whereNull('agent_user_id');
        }

        $perPage = min(max($request->integer('per_page', 15), 1), 50);
        $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }

    /**
     * Store a newly created order
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'nullable|exists:users,id',
            'order_type_id' => 'required|exists:order_types,id',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'origin_location_id' => 'nullable|exists:locations,id',
            'destination_location_id' => 'nullable|exists:locations,id',
            'shop_id' => 'nullable|exists:shops,id',
            'delivery_fee' => 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'payment_method' => 'nullable|string|in:'.implode(',', Payment::METHODS),
            'payer_phone' => 'nullable|string|max:20',
            'scheduled_at' => 'nullable|date|after:now',
            'items' => 'nullable|array',
            'items.*.product_id' => 'required_with:items|exists:products,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthenticated',
            ], 401);
        }

        $user->loadMissing('role');
        $isStaff = in_array($user->role?->name, ['admin', 'manager'], true);
        $customerId = $user->id;
        if ($request->filled('user_id')) {
            if (! $isStaff) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized',
                ], 403);
            }
            $customerId = (int) $request->user_id;
        }

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => $customerId,
                'order_type_id' => $request->order_type_id,
                'origin' => $request->origin,
                'destination' => $request->destination,
                'origin_location_id' => $request->origin_location_id,
                'destination_location_id' => $request->destination_location_id,
                'shop_id' => $request->shop_id,
                'delivery_fee' => $request->delivery_fee,
                'weight' => $request->weight,
                'notes' => $request->notes,
                'scheduled_at' => $request->scheduled_at,
                'order_status_id' => 1, // Pendente
                'code' => 'KNG-' . time() . '-' . rand(1000, 9999),
            ]);

            $total_price = $request->delivery_fee;

            // Adicionar itens se fornecidos
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price'],
                        'total' => $item['quantity'] * $item['price'],
                    ]);

                    $total_price += $orderItem->total;
                }
            }

            $order->update(['total_price' => $total_price]);

            if ($request->filled('payment_method')) {
                $method = $request->payment_method;
                $phone = $request->payer_phone;
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $total_price,
                    'payment_method' => $method,
                    'status' => 'pending',
                    'description' => $phone
                        ? ($method === 'cash' ? 'Dinheiro na entrega' : strtoupper($method).' • '.$phone)
                        : null,
                ]);
            }

            DB::commit();

            $this->dispatch->dispatchCreatedOrder($order->fresh());

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order->fresh()->load(['orderItems.product', 'orderType', 'orderStatus', 'payments', 'agent'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified order
     */
    public function show(Request $request, string $id)
    {
        $order = Order::with([
            'user', 'agent.profile', 'shop', 'vehicle.vehicleType', 'orderType', 'orderStatus', 
            'orderItems.product', 'payments', 'trackingOrders.orderStatus'
        ])->findOrFail($id);

        $user = $request->user();
        $user?->loadMissing('role');
        if ($user?->role?->name === 'customer' && $order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $payload = $order->toArray();
        $payload['current_location'] = $order->liveLocation();

        return response()->json([
            'status' => 'success',
            'data' => $payload,
        ]);
    }

    /**
     * Update the specified order
     */
    public function update(Request $request, string $id)
    {
        $order = Order::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'agent_user_id' => 'nullable|exists:users,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'order_status_id' => 'nullable|exists:order_statuses,id',
            'notes' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
            'collected_at' => 'nullable|date',
            'delivered_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order->update($request->only([
            'agent_user_id', 'vehicle_id', 'order_status_id', 'notes',
            'scheduled_at', 'collected_at', 'delivered_at'
        ]));

        return response()->json([
            'status' => 'success',
            'message' => 'Order updated successfully',
            'data' => $order->load(['orderType', 'orderStatus', 'agent', 'vehicle'])
        ]);
    }

    /**
     * Cancel the specified order
     */
    public function destroy(Request $request, string $id)
    {
        $order = Order::with(['orderStatus', 'payments', 'agent', 'user'])->findOrFail($id);
        $user = $request->user();
        $user?->loadMissing('role');

        $isOwner = $user && $order->user_id === $user->id;
        $isStaff = in_array($user?->role?->name, ['admin', 'manager'], true);
        if (! $isOwner && ! $isStaff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $status = $order->orderStatus?->name;
        if (in_array($status, ['collected', 'in_transit', 'delivering'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel this order after collection',
            ], 422);
        }

        if (in_array($status, ['delivered', 'cancelled', 'failed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel this order',
            ], 422);
        }

        $order->update(['order_status_id' => 9]);
        $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
        $order->payments()->where('status', 'completed')->update(['status' => 'refunded']);

        TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => 9,
            'description' => $isStaff ? 'Pedido cancelado pela operação' : 'Pedido cancelado pelo cliente',
            'local' => $order->origin,
            'updated_by' => $user->id,
        ]);

        $this->dispatch->releaseVehicle($order);
        $this->dispatch->notifyOrderCancelled($order->fresh(['agent', 'user', 'orderStatus']));

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully',
            'data' => $order->fresh()->load([
                'orderType',
                'orderStatus',
                'payments',
                'trackingOrders.orderStatus',
            ]),
        ]);
    }

    /**
     * Assign driver to order
     */
    public function assignDriver(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'driver_id' => 'required|exists:users,id',
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($id);
        $driver = \App\Models\User::findOrFail($request->driver_id);

        $order->update([
            'vehicle_id' => $request->vehicle_id,
        ]);
        $this->dispatch->assignOrderToDriver($order, $driver, 'Motorista atribuído pelo administrador');

        return response()->json([
            'status' => 'success',
            'message' => 'Driver assigned successfully',
            'data' => $order->fresh()->load(['agent', 'vehicle', 'orderStatus'])
        ]);
    }

    /**
     * Unassigned orders waiting for a driver.
     */
    public function availableForDriver(Request $request)
    {
        $orders = Order::whereNull('agent_user_id')
            ->whereHas('orderStatus', function ($query) {
                $query->whereIn('name', ['pending', 'confirmed']);
            })
            ->with(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Orders assigned to the authenticated driver.
     */
    public function driverOrders(Request $request)
    {
        $query = Order::where('agent_user_id', $request->user()->id)
            ->with(['user', 'shop', 'vehicle', 'orderType', 'orderStatus', 'orderItems.product', 'payments']);

        if ($request->filled('status')) {
            $query->whereHas('orderStatus', function ($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders,
        ]);
    }

    /**
     * Driver claims an unassigned order.
     */
    public function accept(Request $request, string $id)
    {
        $user = $request->user();
        $user->loadMissing('role');

        return DB::transaction(function () use ($user, $id) {
            $order = Order::with('orderStatus')->lockForUpdate()->findOrFail($id);

            if ($order->agent_user_id && $order->agent_user_id !== $user->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order already assigned',
                ], 409);
            }

            if ($order->agent_user_id === $user->id) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order already accepted',
                    'data' => $this->driverOrderPayload($order),
                ]);
            }

            if (! in_array($order->orderStatus->name, ['pending', 'confirmed'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order cannot be accepted in the current status',
                ], 422);
            }

            $this->dispatch->assignOrderToDriver($order, $user, 'Motorista aceitou o pedido');

            return response()->json([
                'status' => 'success',
                'message' => 'Order accepted',
                'data' => $this->driverOrderPayload($order->fresh()),
            ]);
        });
    }

    /**
     * Driver advances the order to the next status in the delivery flow.
     */
    public function advanceStatus(Request $request, string $id)
    {
        $user = $request->user();
        $order = Order::with('orderStatus')->findOrFail($id);

        if ($order->agent_user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $nextId = $this->nextDriverStatusId($order->orderStatus->name);
        if (! $nextId) {
            return response()->json([
                'status' => 'error',
                'message' => 'No further status for this order',
            ], 422);
        }

        $order->update(['order_status_id' => $nextId]);
        $order->load('orderStatus');

        $local = in_array($order->orderStatus->name, ['collected', 'in_transit', 'delivering', 'delivered'], true)
            ? $order->destination
            : $order->origin;

        $latitude = $request->input('latitude');
        $longitude = $request->input('longitude');

        TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => $nextId,
            'description' => $order->orderStatus->display_name ?? $order->orderStatus->name,
            'local' => $request->input('local', $local),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'updated_by' => $user->id,
        ]);

        if ($latitude !== null && $longitude !== null) {
            $order->update([
                'current_latitude' => $latitude,
                'current_longitude' => $longitude,
                'location_updated_at' => now(),
            ]);
        }

        if ($order->orderStatus->name === 'collected') {
            $order->update(['collected_at' => now()]);
        } elseif ($order->orderStatus->name === 'delivered') {
            $order->update(['delivered_at' => now()]);
            $this->completeCashPayments($order);
            $this->dispatch->releaseVehicle($order);
        }

        $this->dispatch->notifyStatusChange(
            $order->fresh(['user', 'orderStatus']),
            $order->orderStatus->display_name ?? $order->orderStatus->name
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated',
            'data' => $this->driverOrderPayload($order->fresh()),
        ]);
    }

    private function nextDriverStatusId(string $currentName): ?int
    {
        return match ($currentName) {
            'assigned' => 4,
            'collecting' => 5,
            'collected' => 6,
            'in_transit' => 7,
            'delivering' => 8,
            default => null,
        };
    }

    private function driverOrderPayload(Order $order): Order
    {
        return $order->load([
            'user',
            'shop',
            'vehicle',
            'orderType',
            'orderStatus',
            'orderItems.product',
            'payments',
        ]);
    }

    private function completeCashPayments(Order $order): void
    {
        $order->payments()
            ->where('payment_method', 'cash')
            ->where('status', 'pending')
            ->update([
                'status' => 'completed',
                'paid_at' => now(),
            ]);
    }
}
