<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\DispatchService;
use Illuminate\Http\Request;

class DispatchController extends Controller
{
    public function __construct(private DispatchService $dispatch)
    {
    }

    public function board()
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->dispatch->board(),
        ]);
    }

    public function retry(Request $request, string $id)
    {
        $order = Order::with(['orderStatus', 'agent'])->findOrFail($id);

        if ($order->orderStatus?->is_final) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot dispatch a finished order',
            ], 422);
        }

        $driver = $this->dispatch->tryAssign($order->fresh(), true, false);

        return response()->json([
            'status' => 'success',
            'message' => $driver ? 'Driver assigned' : 'No driver available',
            'data' => [
                'order' => $order->fresh()->load(['agent', 'vehicle', 'orderStatus', 'user']),
                'driver' => $driver,
            ],
        ]);
    }

    public function unassign(Request $request, string $id)
    {
        $order = Order::with('orderStatus')->findOrFail($id);

        try {
            $this->dispatch->unassignOrder($order, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Driver unassigned',
            'data' => $order->fresh()->load(['agent', 'vehicle', 'orderStatus', 'user']),
        ]);
    }
}
