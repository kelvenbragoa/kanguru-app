<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrderExceptionController extends Controller
{
    public function types()
    {
        $types = collect(OrderException::TYPES)->map(fn ($meta, $key) => [
            'type' => $key,
            'label' => $meta['label'],
            'default_cost_bearer' => $meta['bearer'],
        ])->values();

        return response()->json(['status' => 'success', 'data' => $types]);
    }

    public function index(Request $request)
    {
        $query = OrderException::query()
            ->with(['order:id,code,shop_id,user_id', 'reporter:id,name', 'resolver:id,name'])
            ->latest();

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }

        if ($orderId = $request->query('order_id')) {
            $query->where('order_id', $orderId);
        }

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate(min(50, max(1, (int) $request->query('per_page', 20)))),
        ]);
    }

    public function forOrder(Request $request, string $orderId)
    {
        $order = Order::query()->findOrFail($orderId);
        $user = $request->user();
        $isStaff = in_array($user->role?->name, ['admin', 'manager'], true);
        abort_unless($isStaff || (int) $order->user_id === (int) $user->id, 403, 'Unauthorized');

        $items = OrderException::query()
            ->where('order_id', $order->id)
            ->with(['reporter:id,name', 'resolver:id,name'])
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $items]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'type' => 'required|string|in:'.implode(',', array_keys(OrderException::TYPES)),
            'description' => 'nullable|string|max:2000',
            'cost_bearer' => 'nullable|string|in:none,customer,partner,kanguro',
            'cost_amount' => 'nullable|numeric|min:0',
            'photo_url' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $order = Order::query()->findOrFail($request->order_id);
        $user = $request->user();
        $isStaff = in_array($user->role?->name, ['admin', 'manager'], true);
        $isCustomer = (int) $order->user_id === (int) $user->id;

        abort_unless($isStaff || $isCustomer, 403, 'Unauthorized');

        $customerTypes = ['incomplete_order', 'damaged_in_transit', 'wrong_address', 'other'];
        if (! $isStaff && ! in_array($request->type, $customerTypes, true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tipo de excepção não permitido para o cliente',
            ], 422);
        }

        $exception = OrderException::create([
            'order_id' => $order->id,
            'type' => $request->type,
            'description' => $request->description,
            'cost_bearer' => $request->input('cost_bearer', OrderException::defaultBearer($request->type)),
            'cost_amount' => $request->input('cost_amount', 0),
            'status' => OrderException::STATUS_OPEN,
            'photo_url' => $request->photo_url,
            'reported_by' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Excepção registada',
            'data' => $exception->load(['reporter:id,name']),
        ], 201);
    }

    public function resolve(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'cost_bearer' => 'nullable|string|in:none,customer,partner,kanguro',
            'cost_amount' => 'nullable|numeric|min:0',
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $exception = OrderException::query()->findOrFail($id);

        if ($exception->status === OrderException::STATUS_RESOLVED) {
            return response()->json([
                'status' => 'error',
                'message' => 'Excepção já resolvida',
            ], 422);
        }

        $exception->update([
            'status' => OrderException::STATUS_RESOLVED,
            'cost_bearer' => $request->input('cost_bearer', $exception->cost_bearer),
            'cost_amount' => $request->input('cost_amount', $exception->cost_amount),
            'resolution_notes' => $request->resolution_notes,
            'resolved_by' => $request->user()->id,
            'resolved_at' => now(),
        ]);

        if ($exception->type === 'cash_refusal') {
            $exception->loadMissing('order');
            if ($exception->order?->user_id) {
                \App\Models\Profile::query()->updateOrCreate(
                    ['user_id' => $exception->order->user_id],
                    ['cash_blocked' => true]
                );
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Excepção resolvida',
            'data' => $exception->fresh(['reporter:id,name', 'resolver:id,name', 'order:id,code']),
        ]);
    }
}
