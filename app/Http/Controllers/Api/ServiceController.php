<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceBooking;
use App\Models\ServiceOffering;
use App\Models\Shop;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServiceController extends Controller
{
    public function __construct(private SettlementService $settlement)
    {
    }

    public function index(Request $request)
    {
        $query = ServiceOffering::query()
            ->active()
            ->with(['shop:id,name,image_url,type,address'])
            ->whereHas('shop', fn ($q) => $q->where('type', Shop::TYPE_SERVICES))
            ->orderBy('title');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        if ($shopId = $request->query('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 20)));

        return response()->json([
            'status' => 'success',
            'data' => $query->paginate($perPage),
        ]);
    }

    public function show(string $id)
    {
        $offering = ServiceOffering::query()
            ->active()
            ->with(['shop:id,name,image_url,type,address,phone'])
            ->whereHas('shop', fn ($q) => $q->where('type', Shop::TYPE_SERVICES))
            ->findOrFail($id);

        return response()->json(['status' => 'success', 'data' => $offering]);
    }

    public function book(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'service_offering_id' => 'required|exists:service_offerings,id',
            'scheduled_at' => 'required|date|after:now',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|in:mpesa,emola,cash,card',
            'payer_phone' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $offering = ServiceOffering::query()
            ->active()
            ->with('shop')
            ->findOrFail($request->service_offering_id);

        abort_unless($offering->shop?->type === Shop::TYPE_SERVICES, 422, 'Serviço inválido');

        if (in_array($offering->location_type, [ServiceOffering::LOCATION_ONSITE, ServiceOffering::LOCATION_BOTH], true)
            && ! $request->filled('address')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Indique a morada do serviço.',
            ], 422);
        }

        $booking = ServiceBooking::create([
            'code' => ServiceBooking::generateCode(),
            'completion_code' => ServiceBooking::generateCompletionCode(),
            'user_id' => $request->user()->id,
            'shop_id' => $offering->shop_id,
            'service_offering_id' => $offering->id,
            'scheduled_at' => $request->scheduled_at,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'notes' => $request->notes,
            'price' => $offering->price,
            'status' => ServiceBooking::STATUS_PENDING,
            'payment_method' => $request->payment_method,
            'payment_status' => 'pending',
            'payer_phone' => $request->payer_phone,
        ]);

        $booking->load(['shop:id,name,image_url', 'offering']);

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido de serviço enviado',
            'data' => $booking->toCustomerArray(),
        ], 201);
    }

    public function myBookings(Request $request)
    {
        $bookings = ServiceBooking::query()
            ->where('user_id', $request->user()->id)
            ->with(['shop:id,name,image_url', 'offering'])
            ->latest()
            ->paginate(min(50, max(1, (int) $request->query('per_page', 20))));

        $bookings->getCollection()->transform(fn (ServiceBooking $b) => $b->toCustomerArray());

        return response()->json(['status' => 'success', 'data' => $bookings]);
    }

    public function showBooking(Request $request, string $id)
    {
        $booking = ServiceBooking::query()
            ->with(['shop', 'offering'])
            ->findOrFail($id);

        $user = $request->user();
        $isOwner = (int) $booking->user_id === (int) $user->id;
        $isProvider = $booking->shop && (int) $booking->shop->owner_user_id === (int) $user->id;
        $isStaff = in_array($user->role?->name, ['admin', 'manager'], true);

        abort_unless($isOwner || $isProvider || $isStaff, 403, 'Unauthorized');

        if ($isOwner && ! $isProvider && ! $isStaff) {
            return response()->json(['status' => 'success', 'data' => $booking->toCustomerArray()]);
        }

        $payload = $booking->toArray();
        $payload['completion_code'] = null;

        return response()->json(['status' => 'success', 'data' => $payload]);
    }

    public function cancel(Request $request, string $id)
    {
        $booking = ServiceBooking::query()->findOrFail($id);
        abort_unless((int) $booking->user_id === (int) $request->user()->id, 403, 'Unauthorized');

        if (! $booking->canCancelByCustomer()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já não é possível cancelar este pedido.',
            ], 422);
        }

        $booking->update([
            'status' => ServiceBooking::STATUS_CANCELLED,
            'cancelled_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Pedido cancelado',
            'data' => $booking->fresh(['shop', 'offering'])->toCustomerArray(),
        ]);
    }

    public function accept(Request $request, string $id)
    {
        return $this->transitionOwned($request, $id, ServiceBooking::STATUS_PENDING, function (ServiceBooking $booking) {
            $booking->update([
                'status' => ServiceBooking::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);
        }, 'Pedido aceite');
    }

    public function reject(Request $request, string $id)
    {
        $reason = (string) $request->input('reason', 'Prestador recusou o pedido');

        return $this->transitionOwned($request, $id, ServiceBooking::STATUS_PENDING, function (ServiceBooking $booking) use ($reason) {
            $booking->update([
                'status' => ServiceBooking::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'cancelled_at' => now(),
            ]);
        }, 'Pedido recusado');
    }

    public function start(Request $request, string $id)
    {
        return $this->transitionOwned($request, $id, ServiceBooking::STATUS_ACCEPTED, function (ServiceBooking $booking) {
            $booking->update([
                'status' => ServiceBooking::STATUS_IN_PROGRESS,
                'started_at' => now(),
            ]);
        }, 'Serviço iniciado');
    }

    public function complete(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'completion_code' => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        return DB::transaction(function () use ($request, $id) {
            $booking = ServiceBooking::with('shop')->lockForUpdate()->findOrFail($id);
            $this->assertOwnsBooking($request->user(), $booking);

            if (! in_array($booking->status, [ServiceBooking::STATUS_ACCEPTED, ServiceBooking::STATUS_IN_PROGRESS], true)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Só é possível concluir pedidos aceites ou em execução',
                ], 422);
            }

            if ((string) $request->completion_code !== (string) $booking->getAttributes()['completion_code']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Código de conclusão incorrecto',
                ], 422);
            }

            $booking->update([
                'status' => ServiceBooking::STATUS_COMPLETED,
                'started_at' => $booking->started_at ?? now(),
                'completed_at' => now(),
                'payment_status' => $booking->payment_method === 'cash' ? 'pending' : 'completed',
            ]);

            $fresh = $booking->fresh(['shop', 'offering', 'user']);
            $this->settlement->settleService($fresh);

            return response()->json([
                'status' => 'success',
                'message' => 'Serviço concluído',
                'data' => $fresh,
            ]);
        });
    }

    private function transitionOwned(Request $request, string $id, string $from, callable $updater, string $message)
    {
        return DB::transaction(function () use ($request, $id, $from, $updater, $message) {
            $booking = ServiceBooking::with('shop')->lockForUpdate()->findOrFail($id);
            $this->assertOwnsBooking($request->user(), $booking);

            if ($booking->status !== $from) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Estado do pedido não permite esta acção',
                ], 422);
            }

            $updater($booking);

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $booking->fresh(['shop', 'offering', 'user']),
            ]);
        });
    }

    private function assertOwnsBooking($user, ServiceBooking $booking): void
    {
        abort_unless(
            $booking->shop && (int) $booking->shop->owner_user_id === (int) $user->id,
            403,
            'Unauthorized'
        );
    }
}
