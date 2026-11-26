<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HotelBooking;
use App\Models\RoomDateBlock;
use App\Models\RoomType;
use App\Models\Shop;
use App\Services\HotelAvailabilityService;
use App\Services\SettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class HotelController extends Controller
{
    public function __construct(
        private HotelAvailabilityService $availability,
        private SettlementService $settlement,
    ) {
    }

    public function rooms(string $shopId)
    {
        $shop = Shop::query()->where('type', Shop::TYPE_HOTEL)->findOrFail($shopId);

        $rooms = RoomType::query()
            ->where('shop_id', $shop->id)
            ->active()
            ->orderBy('price_per_night')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'shop' => $shop,
                'rooms' => $rooms,
            ],
        ]);
    }

    public function quote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:20',
            'payment_mode' => 'nullable|string|in:full,deposit',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $room = RoomType::query()->active()->with('shop')->findOrFail($request->room_type_id);
            abort_unless($room->shop?->type === Shop::TYPE_HOTEL, 422, 'Quarto inválido');

            $quote = $this->availability->quote(
                $room,
                $request->check_in,
                $request->check_out,
                (int) $request->guests,
                $request->input('payment_mode', 'full'),
            );

            return response()->json(['status' => 'success', 'data' => $quote]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'room_type_id' => 'required|exists:room_types,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
            'guests' => 'required|integer|min:1|max:20',
            'payment_mode' => 'nullable|string|in:full,deposit',
            'payment_method' => 'nullable|string|in:mpesa,emola,cash,card',
            'payer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $user->loadMissing('profile');
        if ($request->input('payment_method') === 'cash' && $user->profile?->cash_blocked) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pagamento em numerário bloqueado nesta conta. Usa M-Pesa, e-Mola ou cartão.',
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request) {
                $room = RoomType::query()->active()->with('shop')->lockForUpdate()->findOrFail($request->room_type_id);
                abort_unless($room->shop?->type === Shop::TYPE_HOTEL, 422, 'Quarto inválido');

                $paymentMode = $request->input('payment_mode', 'full');
                $quote = $this->availability->quote(
                    $room,
                    $request->check_in,
                    $request->check_out,
                    (int) $request->guests,
                    $paymentMode,
                );

                $code = HotelBooking::generateCode();
                $checkin = HotelBooking::generateCheckinCode();

                $booking = HotelBooking::create([
                    'code' => $code,
                    'checkin_code' => $checkin,
                    'qr_payload' => HotelBooking::generateQrPayload($code, $checkin),
                    'user_id' => $request->user()->id,
                    'shop_id' => $room->shop_id,
                    'room_type_id' => $room->id,
                    'check_in' => $quote['check_in'],
                    'check_out' => $quote['check_out'],
                    'guests' => $quote['guests'],
                    'nights' => $quote['nights'],
                    'price_per_night' => $quote['price_per_night'],
                    'total_price' => $quote['total_price'],
                    'payment_mode' => $paymentMode,
                    'deposit_amount' => $quote['deposit_amount'],
                    'amount_paid' => 0,
                    'status' => HotelBooking::STATUS_PENDING,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'pending',
                    'payer_phone' => $request->payer_phone,
                    'notes' => $request->notes,
                    'confirm_deadline_at' => now()->addMinutes((int) config('kanguro.hotel_confirm_timeout_minutes', 60)),
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Reserva pedida — aguarda confirmação do hotel',
                    'data' => $booking->load(['shop', 'roomType']),
                ], 201);
            });
        } catch (\InvalidArgumentException $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 422);
        }
    }

    public function index(Request $request)
    {
        $bookings = HotelBooking::query()
            ->where('user_id', $request->user()->id)
            ->with(['shop', 'roomType'])
            ->orderByDesc('created_at')
            ->paginate(min(max($request->integer('per_page', 15), 1), 50));

        return response()->json(['status' => 'success', 'data' => $bookings]);
    }

    public function show(Request $request, string $id)
    {
        $booking = HotelBooking::with(['shop', 'roomType', 'user'])->findOrFail($id);
        $user = $request->user();
        $user->loadMissing('role');

        $isOwner = (int) $booking->user_id === (int) $user->id;
        $isStaff = in_array($user->role?->name, ['admin', 'manager'], true);
        $isHotelOwner = $user->role?->name === 'shop_owner'
            && Shop::query()->where('id', $booking->shop_id)->where('owner_user_id', $user->id)->exists();

        if (! $isOwner && ! $isStaff && ! $isHotelOwner) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['status' => 'success', 'data' => $booking]);
    }

    public function confirm(Request $request, string $id)
    {
        $booking = HotelBooking::findOrFail($id);
        if (! $this->canManageHotel($request, $booking)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== HotelBooking::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Reserva não está pendente'], 422);
        }

        $booking->update([
            'status' => HotelBooking::STATUS_CONFIRMED,
            'confirmed_at' => now(),
            'confirm_deadline_at' => null,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reserva confirmada',
            'data' => $booking->fresh(['shop', 'roomType', 'user']),
        ]);
    }

    public function reject(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:500',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $booking = HotelBooking::findOrFail($id);
        if (! $this->canManageHotel($request, $booking)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== HotelBooking::STATUS_PENDING) {
            return response()->json(['status' => 'error', 'message' => 'Reserva não está pendente'], 422);
        }

        $booking->update([
            'status' => HotelBooking::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'reject_reason' => $request->input('reason', 'Recusada pelo hotel'),
            'payment_status' => $booking->payment_status === 'completed' ? 'refunded' : 'failed',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reserva rejeitada',
            'data' => $booking->fresh(['shop', 'roomType', 'user']),
        ]);
    }

    public function cancel(Request $request, string $id)
    {
        $booking = HotelBooking::with('roomType')->findOrFail($id);
        $user = $request->user();
        $user->loadMissing('role');

        $isOwner = (int) $booking->user_id === (int) $user->id;
        $isStaff = in_array($user->role?->name, ['admin', 'manager'], true);
        $isHotelOwner = $this->canManageHotel($request, $booking);

        if (! $isOwner && ! $isStaff && ! $isHotelOwner) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if (! $booking->canCancel() && ! $isStaff && ! $isHotelOwner) {
            return response()->json([
                'status' => 'error',
                'message' => 'Já não é possível cancelar esta reserva gratuitamente',
            ], 422);
        }

        if (in_array($booking->status, [HotelBooking::STATUS_CANCELLED, HotelBooking::STATUS_COMPLETED, HotelBooking::STATUS_NO_SHOW], true)) {
            return response()->json(['status' => 'error', 'message' => 'Reserva já encerrada'], 422);
        }

        $booking->update([
            'status' => HotelBooking::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'payment_status' => $booking->payment_status === 'completed' ? 'refunded' : 'failed',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reserva cancelada',
            'data' => $booking->fresh(['shop', 'roomType']),
        ]);
    }

    public function checkIn(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'checkin_code' => 'nullable|string|size:4',
            'qr_payload' => 'nullable|string|max:64',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $request->filled('checkin_code') && ! $request->filled('qr_payload')) {
            return response()->json(['status' => 'error', 'message' => 'Indica o código ou o QR'], 422);
        }

        $booking = HotelBooking::findOrFail($id);
        $request->user()->loadMissing('role');
        $isStaff = in_array($request->user()->role?->name, ['admin', 'manager'], true);
        if (! $isStaff && ! $this->canManageHotel($request, $booking)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== HotelBooking::STATUS_CONFIRMED) {
            return response()->json(['status' => 'error', 'message' => 'Reserva não está confirmada'], 422);
        }

        $ok = false;
        if ($request->filled('checkin_code') && (string) $request->checkin_code === (string) $booking->checkin_code) {
            $ok = true;
        }
        if ($request->filled('qr_payload')) {
            $payload = (string) $request->qr_payload;
            if ($payload === (string) $booking->qr_payload || $payload === (string) $booking->checkin_code || str_ends_with($payload, '|'.$booking->checkin_code)) {
                $ok = true;
            }
        }

        if (! $ok) {
            return response()->json(['status' => 'error', 'message' => 'Código de check-in incorrecto'], 422);
        }

        $booking->update([
            'status' => HotelBooking::STATUS_CHECKED_IN,
            'checked_in_at' => now(),
            'amount_paid' => $booking->payment_mode === 'deposit'
                ? (float) $booking->deposit_amount
                : (float) $booking->total_price,
            'payment_status' => 'completed',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Check-in registado',
            'data' => $booking->fresh(['shop', 'roomType', 'user']),
        ]);
    }

    public function checkOut(Request $request, string $id)
    {
        $booking = HotelBooking::findOrFail($id);
        $request->user()->loadMissing('role');
        if (! $this->canManageHotel($request, $booking) && ! in_array($request->user()->role?->name, ['admin', 'manager'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== HotelBooking::STATUS_CHECKED_IN) {
            return response()->json(['status' => 'error', 'message' => 'Reserva não está em estadia'], 422);
        }

        $booking->update([
            'status' => HotelBooking::STATUS_COMPLETED,
            'checked_out_at' => now(),
            'amount_paid' => (float) $booking->total_price,
            'payment_status' => 'completed',
        ]);

        $this->settlement->settleHotel($booking->fresh(['shop']));

        return response()->json([
            'status' => 'success',
            'message' => 'Check-out registado',
            'data' => $booking->fresh(['shop', 'roomType', 'user']),
        ]);
    }

    public function noShow(Request $request, string $id)
    {
        $booking = HotelBooking::findOrFail($id);
        $request->user()->loadMissing('role');
        if (! $this->canManageHotel($request, $booking) && ! in_array($request->user()->role?->name, ['admin', 'manager'], true)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        if ($booking->status !== HotelBooking::STATUS_CONFIRMED) {
            return response()->json(['status' => 'error', 'message' => 'Só reservas confirmadas podem ser no-show'], 422);
        }

        $forfeit = $booking->payment_mode === 'deposit'
            ? (float) $booking->deposit_amount
            : (float) $booking->total_price;

        $booking->update([
            'status' => HotelBooking::STATUS_NO_SHOW,
            'no_show_at' => now(),
            'amount_paid' => $forfeit,
            'payment_status' => 'completed',
        ]);

        $this->settlement->settleHotelNoShow($booking->fresh(['shop']));

        return response()->json([
            'status' => 'success',
            'message' => 'No-show registado — sinal/cobrança aplicada',
            'data' => $booking->fresh(['shop', 'roomType', 'user']),
        ]);
    }

    public function roomBlocks(Request $request, string $roomId)
    {
        $room = RoomType::findOrFail($roomId);
        if (! $this->ownsRoom($request, $room)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $blocks = RoomDateBlock::query()
            ->where('room_type_id', $room->id)
            ->orderBy('date')
            ->get();

        return response()->json(['status' => 'success', 'data' => $blocks]);
    }

    public function storeRoomBlock(Request $request, string $roomId)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'blocked_units' => 'nullable|integer|min:0',
            'price_override' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'error', 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $room = RoomType::findOrFail($roomId);
        if (! $this->ownsRoom($request, $room)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        $block = RoomDateBlock::query()->updateOrCreate(
            ['room_type_id' => $room->id, 'date' => $request->date],
            [
                'blocked_units' => (int) $request->input('blocked_units', 0),
                'price_override' => $request->input('price_override'),
                'note' => $request->note,
            ]
        );

        return response()->json(['status' => 'success', 'data' => $block], 201);
    }

    public function destroyRoomBlock(Request $request, string $roomId, string $blockId)
    {
        $room = RoomType::findOrFail($roomId);
        if (! $this->ownsRoom($request, $room)) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 403);
        }

        RoomDateBlock::query()->where('room_type_id', $room->id)->where('id', $blockId)->delete();

        return response()->json(['status' => 'success', 'message' => 'Bloqueio removido']);
    }

    private function canManageHotel(Request $request, HotelBooking $booking): bool
    {
        $user = $request->user();
        $user->loadMissing('role');

        return $user->role?->name === 'shop_owner'
            && Shop::query()->where('id', $booking->shop_id)->where('owner_user_id', $user->id)->exists();
    }

    private function ownsRoom(Request $request, RoomType $room): bool
    {
        $user = $request->user();
        $user->loadMissing('role');
        if (in_array($user->role?->name, ['admin', 'manager'], true)) {
            return true;
        }

        return $user->role?->name === 'shop_owner'
            && Shop::query()->where('id', $room->shop_id)->where('owner_user_id', $user->id)->exists();
    }
}
