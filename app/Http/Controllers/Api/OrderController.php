<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderException;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\SupportMessage;
use App\Models\TrackingOrder;
use App\Models\User;
use App\Services\DispatchService;
use App\Services\DriverWalletService;
use App\Services\NotificationService;
use App\Services\ProductStockService;
use App\Services\SettlementService;
use App\Services\TaxiFare;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function __construct(
        private DispatchService $dispatch,
        private ProductStockService $stock,
        private NotificationService $notifications,
        private SettlementService $settlement,
    ) {
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
            'coupon_code' => 'nullable|string|max:40',
            'scheduled_at' => 'nullable|date|after:now',
            'origin_latitude' => 'nullable|numeric|between:-90,90',
            'origin_longitude' => 'nullable|numeric|between:-180,180',
            'destination_latitude' => 'nullable|numeric|between:-90,90',
            'destination_longitude' => 'nullable|numeric|between:-180,180',
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

        $user->loadMissing(['role', 'profile']);
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

        if ($request->input('payment_method') === 'cash') {
            $cashProfile = $isStaff && $customerId !== (int) $user->id
                ? \App\Models\User::query()->with('profile')->find($customerId)?->profile
                : $user->profile;
            if ($cashProfile?->cash_blocked) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pagamento em numerário bloqueado nesta conta. Usa M-Pesa, e-Mola ou cartão.',
                ], 422);
            }
        }

        $type = \App\Models\OrderType::find($request->order_type_id);
        $isTaxi = $type?->name === 'Táxi';
        $hasTaxiCoords = $request->filled('origin_latitude')
            && $request->filled('origin_longitude')
            && $request->filled('destination_latitude')
            && $request->filled('destination_longitude');

        if ($isTaxi && ! $hasTaxiCoords && ! $isStaff) {
            return response()->json([
                'status' => 'error',
                'message' => 'Indica a origem e o destino do táxi.',
            ], 422);
        }

        $deliveryFee = (float) $request->delivery_fee;
        if ($isTaxi && $hasTaxiCoords) {
            $deliveryFee = TaxiFare::quote(
                (float) $request->origin_latitude,
                (float) $request->origin_longitude,
                (float) $request->destination_latitude,
                (float) $request->destination_longitude,
            )['amount'];
        }

        DB::beginTransaction();

        try {
            $isShopDelivery = $request->filled('shop_id') && ! $isTaxi;

            $order = Order::create([
                'user_id' => $customerId,
                'order_type_id' => $request->order_type_id,
                'origin' => $request->origin,
                'destination' => $request->destination,
                'origin_latitude' => $request->origin_latitude,
                'origin_longitude' => $request->origin_longitude,
                'destination_latitude' => $request->destination_latitude,
                'destination_longitude' => $request->destination_longitude,
                'origin_location_id' => $request->origin_location_id,
                'destination_location_id' => $request->destination_location_id,
                'shop_id' => $request->shop_id,
                'delivery_fee' => $deliveryFee,
                'weight' => $request->weight,
                'notes' => $request->notes,
                'scheduled_at' => $request->scheduled_at,
                'order_status_id' => Order::statusId('pending') ?? 1,
                'code' => 'KNG-' . time() . '-' . rand(1000, 9999),
                'delivery_code' => ($isShopDelivery || $isTaxi) ? Order::generateDeliveryCode() : null,
            ]);

            $total_price = $deliveryFee;

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

                $this->stock->reserveForOrder($order->fresh(['orderItems']));
            }

            $subtotal = $total_price - $deliveryFee;
            $discount = 0;
            $couponCode = null;
            if ($request->filled('coupon_code')) {
                $coupon = Coupon::query()
                    ->whereRaw('upper(code) = ?', [strtoupper($request->coupon_code)])
                    ->first();

                if (! $coupon) {
                    throw new \InvalidArgumentException('Cupão inválido.');
                }

                $discount = $coupon->discountFor($subtotal, $deliveryFee);
                $couponCode = $coupon->code;
            }

            $serviceFee = $isTaxi
                ? $this->settlement->serviceFeeFor($deliveryFee)
                : $this->settlement->serviceFeeFor(max(0, $subtotal - $discount));
            $total_price = max(0, round($total_price - $discount + $serviceFee, 2));

            $order->update([
                'total_price' => $total_price,
                'discount_amount' => $discount,
                'coupon_code' => $couponCode,
                'service_fee' => $serviceFee,
                'accept_deadline_at' => $isShopDelivery ? now()->addMinutes((int) config('kanguro.partner_accept_timeout_minutes')) : null,
            ]);

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

            $fresh = $order->fresh(['orderItems.product', 'orderType', 'orderStatus', 'payments', 'agent', 'shop.owner']);
            $paymentMethod = $request->input('payment_method');
            $digitalPending = $paymentMethod && $paymentMethod !== 'cash';

            // Entregas de loja: só notificam a loja após pagamento digital confirmado (ou cash).
            if (! $fresh->isShopDelivery()) {
                $this->dispatch->dispatchCreatedOrder($fresh);
            } else {
                $this->dispatch->notifyOrderCreated($fresh);
                if (! $digitalPending) {
                    $this->dispatch->notifyShopOfNewOrder($fresh);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order->fresh(['orderItems.product', 'orderType', 'orderStatus', 'payments', 'agent', 'shop']),
            ], 201);

        } catch (\InvalidArgumentException $e) {
            DB::rollback();

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
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

        if ($user?->role?->name === 'driver') {
            $payload['requires_delivery_code'] = $this->requiresCodeForNextStep($order);
            unset($payload['delivery_code']);
        }

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

        $order->loadMissing('orderType');
        $isTaxi = $order->orderType?->name === 'Táxi';
        $cancelFee = 0.0;
        $exceptionType = 'customer_cancel_before_accept';

        if ($isTaxi && in_array($status, ['assigned', 'collecting'], true)) {
            $cancelFee = (float) config('kanguro.taxi_cancel_fee_after_assign');
            $exceptionType = 'customer_no_show';
        } elseif (! $isTaxi && in_array($status, ['preparing', 'ready', 'assigned', 'collecting'], true)) {
            $cancelFee = (float) config('kanguro.delivery_cancel_fee_after_prep');
            $exceptionType = 'customer_cancel_after_prep';
        } elseif (! $isTaxi && in_array($status, ['pending', 'confirmed'], true)) {
            $exceptionType = 'customer_cancel_before_accept';
        }

        $cancelledId = Order::statusId('cancelled') ?? 9;
        $order->update([
            'order_status_id' => $cancelledId,
            'cancel_fee' => $cancelFee,
        ]);
        $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
        if ($cancelFee <= 0) {
            $order->payments()->where('status', 'completed')->update(['status' => 'refunded']);
        }

        TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => $cancelledId,
            'description' => $isStaff
                ? 'Pedido cancelado pela operação'
                : ($cancelFee > 0
                    ? 'Cancelado pelo cliente (taxa '.number_format($cancelFee, 2, ',', '.').' MT)'
                    : 'Pedido cancelado pelo cliente'),
            'local' => $order->origin,
            'updated_by' => $user->id,
        ]);

        OrderException::create([
            'order_id' => $order->id,
            'type' => $exceptionType,
            'description' => $isStaff ? 'Cancelado pela operação' : 'Cancelado pelo cliente',
            'cost_bearer' => OrderException::defaultBearer($exceptionType),
            'cost_amount' => $cancelFee,
            'status' => OrderException::STATUS_OPEN,
            'reported_by' => $user->id,
        ]);

        $this->stock->releaseForOrder($order);
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
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->whereNull('shop_id')
                        ->whereHas('orderStatus', fn ($s) => $s->whereIn('name', ['pending', 'confirmed']));
                })->orWhere(function ($q) {
                    $q->whereNotNull('shop_id')
                        ->whereHas('orderStatus', fn ($s) => $s->whereIn('name', ['preparing', 'ready', 'confirmed']))
                        ->where(function ($c) {
                            $c->whereNull('courier_call_at')
                                ->orWhere('courier_call_at', '<=', now());
                        });
                });
            })
            ->with(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments'])
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->filter(fn (Order $order) => $this->dispatch->driverCanTake($order, $request->user()))
            ->values();

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

            $acceptable = $order->shop_id
                ? ['preparing', 'ready', 'confirmed']
                : ['pending', 'confirmed'];

            if (! in_array($order->orderStatus->name, $acceptable, true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Order cannot be accepted in the current status',
                ], 422);
            }

            if ($order->shop_id && $order->courier_call_at && $order->courier_call_at->isFuture()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Ainda não é hora de chamar o estafeta',
                ], 422);
            }

            if (! $this->dispatch->driverCanTake($order, $user)) {
                if (! app(DriverWalletService::class)->canReceiveJobs($user)) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Saldo insuficiente. Recarrega a carteira Kanguro para aceitar viagens.',
                        'data' => [
                            'wallet' => app(DriverWalletService::class)->summary($user),
                            'needs_topup' => true,
                        ],
                    ], 422);
                }

                return response()->json([
                    'status' => 'error',
                    'message' => 'Este táxi só pode ser aceite por moto ou carro.',
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
        $order = Order::with(['orderStatus', 'orderType'])->findOrFail($id);

        if ($order->agent_user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $nextId = $this->nextDriverStatusId($order);
        if (! $nextId) {
            return response()->json([
                'status' => 'error',
                'message' => 'No further status for this order',
            ], 422);
        }

        $needsCode = $this->statusTransitionNeedsCode($order, (int) $nextId);
        if ($needsCode) {
            $code = (string) $request->input('delivery_code', '');
            if ($code === '' || $code !== (string) $order->delivery_code) {
                $isTaxi = $order->orderType?->name === 'Táxi';

                return response()->json([
                    'status' => 'error',
                    'message' => $isTaxi
                        ? 'Código do passageiro incorrecto'
                        : 'Código de entrega incorrecto',
                ], 422);
            }
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
            'description' => $this->trackingDescription($order),
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
        } elseif ($order->orderStatus->name === 'delivering') {
            $order->update(['arrived_customer_at' => $order->arrived_customer_at ?? now()]);
        } elseif ($order->orderStatus->name === 'collecting') {
            $order->update(['arrived_partner_at' => $order->arrived_partner_at ?? now()]);
        } elseif ($order->orderStatus->name === 'delivered') {
            $waitFee = 0.0;
            if ($order->orderType?->name === 'Táxi' && $order->arrived_partner_at && $order->collected_at) {
                $waitMinutes = max(
                    0,
                    $order->arrived_partner_at->diffInMinutes($order->collected_at) - (int) config('kanguro.free_wait_minutes')
                );
                $waitFee = round($waitMinutes * (float) config('kanguro.wait_fee_per_minute'), 2);
            }
            $order->update([
                'delivered_at' => now(),
                'wait_fee' => $waitFee,
                'total_price' => round((float) $order->total_price + $waitFee, 2),
                'delivery_fee' => round((float) $order->delivery_fee + $waitFee, 2),
            ]);
            $this->completeCashPayments($order);
            $this->dispatch->releaseVehicle($order);
            $this->settlement->settleOrder($order->fresh(['shop', 'orderType', 'orderItems', 'orderStatus']));
        }

        $this->dispatch->notifyStatusChange(
            $order->fresh(['user', 'orderStatus', 'orderType']),
            $this->trackingDescription($order)
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status updated',
            'data' => $this->driverOrderPayload($order->fresh()),
        ]);
    }

    /**
     * Loja/admin aceita o pedido e agenda a chamada do estafeta.
     */
    public function shopAccept(Request $request, string $id)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'preparation_minutes' => 'required|integer|min:1|max:180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $prep = (int) $request->preparation_minutes;
        $margin = 2;

        return DB::transaction(function () use ($id, $user, $prep, $margin) {
            $order = Order::with(['orderStatus', 'orderType', 'user', 'shop'])->lockForUpdate()->findOrFail($id);

            if ($denied = $this->denyUnlessCanManageShopOrder($user, $order)) {
                return $denied;
            }

            if (! $order->isShopDelivery()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este pedido não é de loja',
                ], 422);
            }

            if (! in_array($order->orderStatus->name, ['pending', 'confirmed'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pedido já aceite ou em progresso',
                ], 422);
            }

            $courierCallAt = $prep <= 15
                ? now()
                : now()->addMinutes(max(0, $prep - $margin));

            $preparingId = Order::statusId('preparing');
            $order->update([
                'order_status_id' => $preparingId ?? $order->order_status_id,
                'preparation_minutes' => $prep,
                'courier_call_at' => $courierCallAt,
                'shop_accepted_at' => now(),
            ]);

            TrackingOrder::create([
                'order_id' => $order->id,
                'order_status_id' => $order->order_status_id,
                'description' => "Parceiro aceitou — preparação {$prep} min",
                'local' => $order->origin,
                'updated_by' => $user->id,
            ]);

            $fresh = $order->fresh(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments', 'agent']);

            if ($courierCallAt->lte(now())) {
                $this->dispatch->tryAssign($fresh, true, true);
            }

            $this->dispatch->notifyStatusChange($fresh, 'Em preparação');

            return response()->json([
                'status' => 'success',
                'message' => 'Pedido aceite pela loja',
                'data' => $fresh->fresh(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments', 'agent']),
            ]);
        });
    }

    /**
     * Loja/admin marca o pedido como pronto para recolha.
     */
    public function shopReady(Request $request, string $id)
    {
        $user = $request->user();

        return DB::transaction(function () use ($id, $user) {
            $order = Order::with(['orderStatus', 'orderType', 'user', 'shop'])->lockForUpdate()->findOrFail($id);

            if ($denied = $this->denyUnlessCanManageShopOrder($user, $order)) {
                return $denied;
            }

            if (! $order->isShopDelivery()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Este pedido não é de loja',
                ], 422);
            }

            if (! in_array($order->orderStatus->name, ['preparing', 'confirmed'], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Pedido não está em preparação',
                ], 422);
            }

            $readyId = Order::statusId('ready');
            $order->update([
                'order_status_id' => $readyId ?? $order->order_status_id,
                'ready_at' => now(),
                'courier_call_at' => $order->courier_call_at?->isFuture()
                    ? now()
                    : ($order->courier_call_at ?? now()),
            ]);

            TrackingOrder::create([
                'order_id' => $order->id,
                'order_status_id' => $order->order_status_id,
                'description' => 'Pedido pronto para recolha',
                'local' => $order->origin,
                'updated_by' => $user->id,
            ]);

            $fresh = $order->fresh(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments', 'agent']);

            if (! $fresh->agent_user_id) {
                $this->dispatch->tryAssign($fresh, true, true);
            }

            $this->dispatch->notifyStatusChange($fresh, 'Pronto para recolha');

            return response()->json([
                'status' => 'success',
                'message' => 'Pedido marcado como pronto',
                'data' => $fresh->fresh(['user', 'shop', 'orderType', 'orderStatus', 'orderItems.product', 'payments', 'agent']),
            ]);
        });
    }

    private function nextDriverStatusId(Order $order): ?int
    {
        $currentName = $order->orderStatus->name;
        if ($order->orderType?->name === 'Táxi') {
            return match ($currentName) {
                'assigned' => 4,
                'collecting' => 5,
                'collected' => 8,
                default => null,
            };
        }

        return match ($currentName) {
            'assigned' => 4,
            'collecting' => 5,
            'collected' => 6,
            'in_transit' => 7,
            'delivering' => 8,
            default => null,
        };
    }

    private function trackingDescription(Order $order): string
    {
        if ($order->orderType?->name === 'Táxi') {
            return match ($order->orderStatus->name) {
                'assigned' => 'Táxi a caminho',
                'collecting' => 'A caminho do passageiro',
                'collected' => 'Passageiro a bordo',
                'delivered' => 'Chegou ao destino',
                default => $order->orderStatus->display_name ?? $order->orderStatus->name,
            };
        }

        return $order->orderStatus->display_name ?? $order->orderStatus->name;
    }

    private function driverOrderPayload(Order $order): array
    {
        $order->load([
            'user',
            'shop',
            'vehicle',
            'orderType',
            'orderStatus',
            'orderItems.product',
            'payments',
        ]);

        $payload = $order->toArray();
        $payload['requires_delivery_code'] = $this->requiresCodeForNextStep($order);
        unset($payload['delivery_code']);

        return $payload;
    }

    private function statusTransitionNeedsCode(Order $order, int $nextId): bool
    {
        if (! filled($order->delivery_code)) {
            return false;
        }

        $nextName = \App\Models\OrderStatus::query()->where('id', $nextId)->value('name');
        if ($order->orderType?->name === 'Táxi') {
            return $nextName === 'collected';
        }

        return $nextName === 'delivered';
    }

    private function requiresCodeForNextStep(Order $order): bool
    {
        if (! filled($order->delivery_code)) {
            return false;
        }

        $nextId = $this->nextDriverStatusId($order);
        if (! $nextId) {
            return false;
        }

        return $this->statusTransitionNeedsCode($order, (int) $nextId);
    }

    public function emergency(Request $request, string $id)
    {
        $order = Order::with(['orderType', 'orderStatus', 'user', 'agent'])->findOrFail($id);
        $user = $request->user();
        $isCustomer = (int) $order->user_id === (int) $user->id;
        $isDriver = (int) $order->agent_user_id === (int) $user->id;
        abort_unless($isCustomer || $isDriver, 403, 'Unauthorized');

        if (in_array($order->orderStatus?->name, ['delivered', 'cancelled', 'failed'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pedido já encerrado',
            ], 422);
        }

        $note = (string) $request->input('note', 'Botão de emergência activado');

        $order->update(['emergency_at' => now()]);

        SupportMessage::create([
            'user_id' => $user->id,
            'subject' => 'Emergência · '.$order->code,
            'message' => $note.' | Origem: '.$order->origin.' | Destino: '.$order->destination,
        ]);

        OrderException::create([
            'order_id' => $order->id,
            'type' => 'other',
            'description' => 'Emergência: '.$note,
            'cost_bearer' => OrderException::BEARER_NONE,
            'status' => OrderException::STATUS_OPEN,
            'reported_by' => $user->id,
        ]);

        $staff = User::query()
            ->whereHas('role', fn ($q) => $q->whereIn('name', ['admin', 'manager']))
            ->where('is_active', true)
            ->get();

        foreach ($staff as $member) {
            $this->notifications->notify(
                $member,
                'Emergência no pedido '.$order->code,
                $note,
                'order_emergency',
                ['order_id' => $order->id, 'order_code' => $order->code]
            );
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Apoio notificado',
            'data' => [
                'order_id' => $order->id,
                'emergency_at' => $order->emergency_at,
            ],
        ]);
    }

    public function rate(Request $request, string $id)
    {
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $order = Order::with(['orderStatus', 'orderType'])->findOrFail($id);
        $user = $request->user();
        $isCustomer = (int) $order->user_id === (int) $user->id;
        $isDriver = (int) $order->agent_user_id === (int) $user->id;
        abort_unless($isCustomer || $isDriver, 403, 'Unauthorized');

        if ($order->orderStatus?->name !== 'delivered') {
            return response()->json([
                'status' => 'error',
                'message' => 'Só pode avaliar depois da viagem/entrega concluída',
            ], 422);
        }

        if ($isCustomer) {
            if ($order->customer_rating) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já avaliou este pedido',
                ], 422);
            }
            $order->update([
                'customer_rating' => $data['rating'],
                'customer_rating_comment' => $data['comment'] ?? null,
            ]);
        } else {
            if ($order->driver_rating) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Já avaliou este pedido',
                ], 422);
            }
            $order->update([
                'driver_rating' => $data['rating'],
                'driver_rating_comment' => $data['comment'] ?? null,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Avaliação registada',
            'data' => $order->fresh(['orderStatus', 'orderType', 'user', 'agent']),
        ]);
    }

    public function share(Request $request, string $id)
    {
        $order = Order::query()->findOrFail($id);
        abort_unless((int) $order->user_id === (int) $request->user()->id, 403, 'Unauthorized');

        $base = rtrim((string) config('app.url'), '/');

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $order->code,
                'url' => $base.'/rastrear?codigo='.$order->code,
                'message' => 'Acompanhe a minha viagem Kanguro: '.$order->code.' — '.$base.'/rastrear?codigo='.$order->code,
            ],
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

    private function denyUnlessCanManageShopOrder(User $user, Order $order): ?\Illuminate\Http\JsonResponse
    {
        $user->loadMissing('role');
        if (in_array($user->role?->name, ['admin', 'manager'], true)) {
            return null;
        }

        if ($user->role?->name === 'shop_owner' && $order->shop_id) {
            $owns = Shop::query()
                ->where('id', $order->shop_id)
                ->where('owner_user_id', $user->id)
                ->exists();
            if ($owns) {
                return null;
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
    }
}
