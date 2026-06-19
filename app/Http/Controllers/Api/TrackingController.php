<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TrackingOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TrackingController extends Controller
{
    /**
     * Get tracking information for an order
     */
    public function getTracking(string $orderCode)
    {
        $order = Order::where('code', $orderCode)
            ->with([
                'orderStatus',
                'agent',
                'vehicle',
                'trackingOrders' => function ($query) {
                    $query->with('orderStatus')->orderBy('created_at', 'asc');
                }
            ])
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
        $user = Auth::user();
        if (!in_array($user->role->name, ['admin', 'manager']) && $order->agent_user_id !== $user->id) {
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

        // Atualizar timestamps específicos
        $statusName = $order->orderStatus->name;
        if ($statusName === 'collected') {
            $order->update(['collected_at' => now()]);
        } elseif ($statusName === 'delivered') {
            $order->update(['delivered_at' => now()]);
        }

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
     * Get real-time location of driver
     */
    public function getDriverLocation(string $orderId)
    {
        $order = Order::with(['agent', 'vehicle', 'trackingOrders' => function ($query) {
            $query->whereNotNull('latitude')
                  ->whereNotNull('longitude')
                  ->orderBy('created_at', 'desc')
                  ->limit(1);
        }])->findOrFail($orderId);

        $lastLocation = $order->trackingOrders->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'order_id' => $order->id,
                'driver' => $order->agent,
                'vehicle' => $order->vehicle,
                'last_location' => $lastLocation ? [
                    'latitude' => $lastLocation->latitude,
                    'longitude' => $lastLocation->longitude,
                    'description' => $lastLocation->description,
                    'local' => $lastLocation->local,
                    'updated_at' => $lastLocation->created_at,
                ] : null
            ]
        ]);
    }

    /**
     * Get orders for driver dashboard
     */
    public function getDriverOrders()
    {
        $user = Auth::user();

        if ($user->role->name !== 'driver') {
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
