<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TrackingOrder;
use App\Services\DispatchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    public function __construct(private DispatchService $dispatch)
    {
    }
    /**
     * Get tracking information for an order
     */
    public function getTracking(string $orderCode)
    {
        $order = Order::query()
            ->with([
                'orderStatus',
                'orderType',
                'agent.profile',
                'vehicle',
                'shop',
                'trackingOrders' => function ($query) {
                    $query->with('orderStatus')->orderBy('created_at', 'asc');
                }
            ])
            ->where(function ($query) use ($orderCode) {
                $query->where('code', $orderCode);
                if (ctype_digit($orderCode)) {
                    $query->orWhere('id', (int) $orderCode);
                }
            })
            ->first();

        if (!$order) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'order' => $order,
                'current_status' => $order->orderStatus,
                'tracking_history' => $order->trackingOrders,
                'driver' => $order->agent,
                'vehicle' => $order->vehicle,
                'current_location' => $order->liveLocation(),
            ]
        ]);
    }

    /**
     * Update order status and location
     */
    public function updateStatus(Request $request, string $orderId)
    {
        $validator = Validator::make($request->all(), [
            'order_status_id' => 'required|exists:order_statuses,id',
            'description' => 'required|string|max:255',
            'local' => 'required|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($orderId);

        // Verificar se o usuário pode atualizar este pedido
        $user = $request->user();
        $user?->loadMissing('role');
        if (!in_array($user?->role?->name, ['admin', 'manager']) && $order->agent_user_id !== $user?->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        // Atualizar status do pedido
        $order->update(['order_status_id' => $request->order_status_id]);

        // Criar registro de rastreamento
        $tracking = TrackingOrder::create([
            'order_id' => $order->id,
            'order_status_id' => $request->order_status_id,
            'description' => $request->description,
            'local' => $request->local,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'updated_by' => $user->id,
        ]);

        if ($request->latitude !== null && $request->longitude !== null) {
            $order->update([
                'current_latitude' => $request->latitude,
                'current_longitude' => $request->longitude,
                'location_updated_at' => now(),
            ]);
        }

        $order->refresh()->load('orderStatus');

        // Atualizar timestamps específicos
        $statusName = $order->orderStatus->name;
        if ($statusName === 'collected') {
            $order->update(['collected_at' => now()]);
        } elseif ($statusName === 'delivered') {
            $order->update(['delivered_at' => now()]);
            $order->payments()
                ->where('payment_method', 'cash')
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'paid_at' => now(),
                ]);
            $this->dispatch->releaseVehicle($order);
        }

        $this->dispatch->notifyStatusChange(
            $order->fresh(['user', 'orderStatus']),
            $order->orderStatus->display_name ?? $order->orderStatus->name
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Tracking updated successfully',
            'data' => [
                'order' => $order->load('orderStatus'),
                'tracking' => $tracking->load('orderStatus')
            ]
        ]);
    }

    /**
     * Driver pings GPS without changing order status.
     */
    public function updateLocation(Request $request, string $orderId)
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $order = Order::with('orderStatus')->findOrFail($orderId);

        if ($order->agent_user_id !== $user?->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        if ($order->orderStatus?->is_final) {
            return response()->json([
                'status' => 'error',
                'message' => 'Order is no longer active',
            ], 422);
        }

        $order->update([
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'location_updated_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Location updated',
            'data' => [
                'order_id' => $order->id,
                'last_location' => $order->liveLocation(),
            ],
        ]);
    }

    /**
     * Get real-time location of driver
     */
    public function getDriverLocation(Request $request, string $orderId)
    {
        $order = Order::with(['agent', 'vehicle', 'orderStatus', 'trackingOrders' => function ($query) {
            $query->whereNotNull('latitude')
                  ->whereNotNull('longitude')
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
        }])->findOrFail($orderId);

        $user = $request->user();
        $user?->loadMissing('role');
        $role = $user?->role?->name;
        $allowed = in_array($role, ['admin', 'manager'], true)
            || $order->user_id === $user?->id
            || $order->agent_user_id === $user?->id;

        if (! $allowed) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized',
            ], 403);
        }

        $live = $order->liveLocation();

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $order->id,
                'driver' => $order->agent,
                'vehicle' => $order->vehicle,
                'last_location' => $live,
            ]
        ]);
    }

    /**
     * Get orders for driver dashboard
     */
    public function getDriverOrders(Request $request)
    {
        $user = $request->user();
        $user?->loadMissing('role');

        if ($user?->role?->name !== 'driver') {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $orders = Order::where('agent_user_id', $user->id)
            ->whereHas('orderStatus', function ($query) {
                $query->where('is_final', false);
            })
            ->with(['orderStatus', 'orderType', 'user'])
            ->orderBy('scheduled_at')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orders
        ]);
    }
}
