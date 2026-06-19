<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.user']);

        // Filtrar por status se fornecido
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filtrar por método de pagamento se fornecido
        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        // Filtrar por data
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }

    /**
     * Store a newly created payment
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id',
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,card,pix,transfer,credit',
            'transaction_id' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Order::findOrFail($request->order_id);

        // Verificar se o valor não excede o total do pedido
        $totalPaid = Payment::where('order_id', $order->id)
            ->where('status', 'completed')
            ->sum('amount');

        if (($totalPaid + $request->amount) > $order->total_price) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment amount exceeds order total'
            ], 422);
        }

        $payment = Payment::create([
            'order_id' => $request->order_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'description' => $request->description,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment created successfully',
            'data' => $payment->load('order')
        ], 201);
    }

    /**
     * Display the specified payment
     */
    public function show(string $id)
    {
        $payment = Payment::with(['order.user'])->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    /**
     * Update the specified payment
     */
    public function update(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|string|in:pending,completed,failed,refunded',
            'transaction_id' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Se status está sendo alterado para completed, definir paid_at
        if ($request->status === 'completed' && $payment->status !== 'completed') {
            $payment->paid_at = now();
        }

        $payment->update($request->only(['status', 'transaction_id', 'description']));

        return response()->json([
            'status' => 'success',
            'message' => 'Payment updated successfully',
            'data' => $payment->load('order')
        ]);
    }

    /**
     * Remove the specified payment
     */
    public function destroy(string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status === 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete completed payment'
            ], 422);
        }

        $payment->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Payment deleted successfully'
        ]);
    }

    /**
     * Confirm payment
     */
    public function confirm(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment is not pending'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'transaction_id' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update([
            'status' => 'completed',
            'paid_at' => now(),
            'transaction_id' => $request->transaction_id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment confirmed successfully',
            'data' => $payment->load('order')
        ]);
    }

    /**
     * Refund payment
     */
    public function refund(Request $request, string $id)
    {
        $payment = Payment::findOrFail($id);

        if ($payment->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only completed payments can be refunded'
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payment->update([
            'status' => 'refunded',
            'description' => ($payment->description ? $payment->description . ' | ' : '') . 'Refund: ' . $request->reason,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Payment refunded successfully',
            'data' => $payment->load('order')
        ]);
    }

    /**
     * Get payments for an order
     */
    public function getByOrder(string $orderId)
    {
        $payments = Payment::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $payments
        ]);
    }
}
