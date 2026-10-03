<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Favorite;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SavedPaymentMethod;
use App\Models\ScooterRental;
use App\Models\Shop;
use App\Models\SupportMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerAccountController extends Controller
{
    public function activity(Request $request)
    {
        $userId = $request->user()->id;

        $orders = Order::query()
            ->with(['orderStatus', 'orderType', 'shop'])
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(function (Order $order) {
                $typeName = $order->orderType?->name ?? ($order->shop_id ? 'Delivery' : 'Pedido');
                $statusName = $order->orderStatus?->name ?? 'pending';
                $isActive = ! (bool) ($order->orderStatus?->is_final);

                return [
                    'kind' => 'order',
                    'id' => $order->id,
                    'code' => $order->code,
                    'service' => $typeName,
                    'title' => $order->shop?->name ?? $typeName,
                    'subtitle' => trim(($order->origin ?? '').' → '.($order->destination ?? ''), ' →'),
                    'status' => $statusName,
                    'status_label' => $order->customer_status_label
                        ?? $order->orderStatus?->display_name
                        ?? $statusName,
                    'amount' => (float) $order->total_price,
                    'is_active' => $isActive,
                    'occurred_at' => optional($order->created_at)?->toIso8601String(),
                    'trackable' => $isActive,
                ];
            });

        $rentals = ScooterRental::query()
            ->with(['scooter', 'startStation', 'endStation'])
            ->where('user_id', $userId)
            ->latest('id')
            ->limit(40)
            ->get()
            ->map(function (ScooterRental $rental) {
                $live = $rental->status === 'active' ? $rental->liveAmount() : null;
                $amount = $live['amount'] ?? (float) ($rental->amount ?? 0);
                $isActive = $rental->status === 'active';
                $from = $rental->startStation?->name ?? '—';
                $to = $rental->endStation?->name;

                return [
                    'kind' => 'scooter',
                    'id' => $rental->id,
                    'code' => $rental->code,
                    'service' => 'Trotinete',
                    'title' => $rental->scooter?->code ?? $rental->code,
                    'subtitle' => $to ? "$from → $to" : "Partida: $from",
                    'status' => $rental->status,
                    'status_label' => $isActive ? 'Em uso' : ($rental->status === 'completed' ? 'Terminado' : $rental->status),
                    'amount' => (float) $amount,
                    'is_active' => $isActive,
                    'occurred_at' => optional($rental->started_at ?? $rental->created_at)?->toIso8601String(),
                    'trackable' => $isActive,
                ];
            });

        $items = $orders->concat($rentals)
            ->sortByDesc(fn (array $item) => $item['occurred_at'] ?? '')
            ->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'active' => $items->where('is_active', true)->values(),
                'history' => $items->where('is_active', false)->values(),
            ],
        ]);
    }

    public function summary(Request $request)
    {
        $user = $request->user();
        $profile = $user->profile;
        $orders = Order::query()
            ->where('user_id', $user->id)
            ->whereNotIn('order_status_id', [9, 10]);

        $count = (clone $orders)->count();
        $spent = (float) (clone $orders)->sum('total_price');
        $savings = (float) (clone $orders)->sum('discount_amount');

        return response()->json([
            'status' => 'success',
            'data' => [
                'orders_count' => $count,
                'points' => (int) floor($spent / 10),
                'savings' => round($savings, 2),
                'notify_push' => (bool) ($profile->notify_push ?? true),
                'notify_email' => (bool) ($profile->notify_email ?? false),
                'notify_sms' => (bool) ($profile->notify_sms ?? false),
            ],
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $data = $request->validate([
            'notify_push' => 'required|boolean',
            'notify_email' => 'required|boolean',
            'notify_sms' => 'required|boolean',
        ]);

        $profile = $request->user()->profile()->firstOrCreate(['user_id' => $request->user()->id]);
        $profile->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Preferências guardadas',
            'data' => $data,
        ]);
    }

    public function favorites(Request $request)
    {
        $favorites = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->with(['shop', 'product'])
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $favorites]);
    }

    public function storeFavorite(Request $request)
    {
        $data = $request->validate([
            'shop_id' => 'nullable|exists:shops,id|required_without:product_id',
            'product_id' => 'nullable|exists:products,id',
        ]);

        $existing = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('shop_id', $data['shop_id'] ?? null)
            ->where('product_id', $data['product_id'] ?? null)
            ->first();

        if ($existing) {
            return response()->json(['status' => 'success', 'data' => $existing], 200);
        }

        $favorite = Favorite::create([
            'user_id' => $request->user()->id,
            'shop_id' => $data['shop_id'] ?? null,
            'product_id' => $data['product_id'] ?? null,
        ]);

        return response()->json(['status' => 'success', 'data' => $favorite->load(['shop', 'product'])], 201);
    }

    public function destroyFavorite(Request $request, string $id)
    {
        Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Removido dos favoritos']);
    }

    public function reviews(Request $request)
    {
        $reviews = Review::query()
            ->where('user_id', $request->user()->id)
            ->with('shop:id,name,image_url')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $reviews]);
    }

    public function shopReviews(string $id)
    {
        Shop::query()->findOrFail($id);
        $reviews = Review::query()
            ->where('shop_id', $id)
            ->with('user:id,name')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $reviews]);
    }

    public function storeReview(Request $request)
    {
        $data = $request->validate([
            'shop_id' => 'required|exists:shops,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $ordered = Order::query()
            ->where('user_id', $request->user()->id)
            ->where('shop_id', $data['shop_id'])
            ->whereNotIn('order_status_id', [9, 10])
            ->exists();

        if (! $ordered) {
            return response()->json([
                'status' => 'error',
                'message' => 'Só pode avaliar um estabelecimento depois de fazer um pedido.',
            ], 422);
        }

        $review = Review::query()->updateOrCreate(
            ['user_id' => $request->user()->id, 'shop_id' => $data['shop_id']],
            ['rating' => $data['rating'], 'comment' => $data['comment'] ?? null],
        );

        return response()->json(['status' => 'success', 'data' => $review->load('shop:id,name,image_url')]);
    }

    public function validateCoupon(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:40',
            'subtotal' => 'required|numeric|min:0',
            'delivery_fee' => 'required|numeric|min:0',
        ]);

        $coupon = Coupon::query()
            ->whereRaw('upper(code) = ?', [strtoupper($data['code'])])
            ->first();

        if (! $coupon) {
            return response()->json(['status' => 'error', 'message' => 'Cupão inválido.'], 422);
        }

        try {
            $discount = $coupon->discountFor((float) $data['subtotal'], (float) $data['delivery_fee']);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }

        $total = max(0, round($data['subtotal'] + $data['delivery_fee'] - $discount, 2));

        return response()->json([
            'status' => 'success',
            'data' => [
                'code' => $coupon->code,
                'discount' => $discount,
                'total' => $total,
            ],
        ]);
    }

    public function paymentMethods(Request $request)
    {
        $methods = SavedPaymentMethod::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->latest()
            ->get();

        return response()->json(['status' => 'success', 'data' => $methods]);
    }

    public function storePaymentMethod(Request $request)
    {
        $data = Validator::make($request->all(), [
            'method' => 'required|string|in:'.implode(',', Payment::METHODS),
            'label' => 'required|string|max:80',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ])->validate();

        if (! empty($data['is_default'])) {
            SavedPaymentMethod::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
        }

        $method = SavedPaymentMethod::create([
            'user_id' => $request->user()->id,
            'method' => $data['method'],
            'label' => $data['label'],
            'phone' => $data['phone'] ?? null,
            'is_default' => (bool) ($data['is_default'] ?? false),
        ]);

        return response()->json(['status' => 'success', 'data' => $method], 201);
    }

    public function destroyPaymentMethod(Request $request, string $id)
    {
        SavedPaymentMethod::query()
            ->where('user_id', $request->user()->id)
            ->where('id', $id)
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Método removido']);
    }

    public function defaultPaymentMethod(Request $request, string $id)
    {
        $method = SavedPaymentMethod::query()
            ->where('user_id', $request->user()->id)
            ->findOrFail($id);

        SavedPaymentMethod::query()->where('user_id', $request->user()->id)->update(['is_default' => false]);
        $method->update(['is_default' => true]);

        return response()->json(['status' => 'success', 'data' => $method]);
    }

    public function support(Request $request)
    {
        $data = $request->validate([
            'subject' => 'required|string|max:120',
            'message' => 'required|string|max:2000',
        ]);

        $message = SupportMessage::create([
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Mensagem enviada. A equipa vai responder pelo contacto da conta.',
            'data' => $message,
        ], 201);
    }
}
