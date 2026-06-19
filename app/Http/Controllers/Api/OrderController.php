<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    /**
     * Display a listing of orders
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Order::with(['user', 'agent', 'shop', 'vehicle', 'orderType', 'orderStatus', 'orderItems.product']);

        // Filtrar por role do usuário
        if ($user->role->name === 'customer') {
            $query->where('user_id', $user->id);
        } elseif ($user->role->name === 'driver') {
            $query->where('agent_user_id', $user->id);
        }

        // Filtros opcionais
        if ($request->has('status')) {
            $query->whereHas('orderStatus', function ($q) use ($request) {
                $q->where('name', $request->status);
            });
        }

        if ($request->has('type')) {
            $query->whereHas('orderType', function ($q) use ($request) {
                $q->where('name', $request->type);
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate(15);

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
            'order_type_id' => 'required|exists:order_types,id',
            'origin' => 'required|string|max:255',
            'destination' => 'required|string|max:255',
            'origin_location_id' => 'nullable|exists:locations,id',
            'destination_location_id' => 'nullable|exists:locations,id',
            'shop_id' => 'nullable|exists:shops,id',
            'delivery_fee' => 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
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

        DB::beginTransaction();

        try {
            $order = Order::create([
                'user_id' => Auth::id(),
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

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Order created successfully',
                'data' => $order->load(['orderItems.product', 'orderType', 'orderStatus'])
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
    public function show(string $id)
    {
        $order = Order::with([
            'user', 'agent', 'shop', 'vehicle.driver', 'orderType', 'orderStatus', 
            'orderItems.product', 'payments', 'trackingOrders.orderStatus'
        ])->findOrFail($id);

        // Verificar permissão
        $user = Auth::user();
        if ($user->role->name === 'customer' && $order->user_id !== $user->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $order
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
     * Remove the specified order
     */
    public function destroy(string $id)
    {
        $order = Order::findOrFail($id);

        // Verificar se pode ser cancelado
        if (in_array($order->orderStatus->name, ['delivered', 'cancelled'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot cancel this order'
            ], 422);
        }

        $order->update(['order_status_id' => 9]); // Cancelado

        return response()->json([
            'status' => 'success',
            'message' => 'Order cancelled successfully'
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

        $order->update([
            'agent_user_id' => $request->driver_id,
            'vehicle_id' => $request->vehicle_id,
            'order_status_id' => 3, // Designado
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Driver assigned successfully',
            'data' => $order->load(['agent', 'vehicle', 'orderStatus'])
        ]);
    }
}
