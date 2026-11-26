<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LostItem;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LostItemController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'description' => 'required|string|max:1000',
            'customer_phone' => 'nullable|string|max:20',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $order = Order::with('orderType')->findOrFail($request->order_id);
        if ((int) $order->user_id !== (int) $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($order->orderType?->name !== 'Táxi' && ! $order->agent_user_id) {
            return response()->json(['status' => 'error', 'message' => 'Pedido sem motorista associado'], 422);
        }

        $item = LostItem::create([
            'order_id' => $order->id,
            'customer_id' => $request->user()->id,
            'driver_id' => $order->agent_user_id,
            'description' => $request->description,
            'customer_phone' => $request->customer_phone,
            'status' => LostItem::STATUS_OPEN,
        ]);

        return response()->json(['status' => 'success', 'message' => 'Objecto perdido reportado', 'data' => $item], 201);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $user->loadMissing('role');
        $role = $user->role?->name;

        $query = LostItem::query()
            ->with([
                'order:id,code',
                'customer:id,name',
                'customer.profile:id,user_id,phone',
                'driver:id,name',
                'driver.profile:id,user_id,phone',
            ])
            ->orderByDesc('created_at');

        if (in_array($role, ['admin', 'manager'], true)) {
            // all
        } elseif ($role === 'driver') {
            $query->where('driver_id', $user->id);
        } else {
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(min(max($request->integer('per_page', 20), 1), 50)),
        ]);
    }

    public function driverRespond(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'driver_response' => 'required|string|max:1000',
            'status' => 'required|in:driver_contacted,returning,returned,closed',
            'return_fee' => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $item = LostItem::findOrFail($id);
        if ((int) $item->driver_id !== (int) $request->user()->id) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $item->update([
            'driver_response' => $request->driver_response,
            'status' => $request->status,
            'return_fee' => $request->input('return_fee', $item->return_fee),
            'resolved_at' => in_array($request->status, ['returned', 'closed'], true) ? now() : null,
        ]);

        return response()->json(['status' => 'success', 'data' => $item->fresh()]);
    }

    public function supportUpdate(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'nullable|string|in:open,driver_contacted,returning,returned,closed,cancelled',
            'support_notes' => 'nullable|string|max:2000',
            'return_fee' => 'nullable|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $item = LostItem::findOrFail($id);
        $item->update([
            'status' => $request->input('status', $item->status),
            'support_notes' => $request->input('support_notes', $item->support_notes),
            'return_fee' => $request->input('return_fee', $item->return_fee),
            'resolved_at' => in_array($request->input('status', $item->status), ['returned', 'closed', 'cancelled'], true)
                ? now()
                : $item->resolved_at,
        ]);

        return response()->json(['status' => 'success', 'data' => $item->fresh()]);
    }
}
