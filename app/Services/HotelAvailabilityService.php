<?php

namespace App\Services;

use App\Models\HotelBooking;
use App\Models\RoomDateBlock;
use App\Models\RoomType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class HotelAvailabilityService
{
    public function nights(string|Carbon $checkIn, string|Carbon $checkOut): int
    {
        $in = Carbon::parse($checkIn)->startOfDay();
        $out = Carbon::parse($checkOut)->startOfDay();

        return max(0, $in->diffInDays($out));
    }

    public function availableUnits(RoomType $room, string|Carbon $checkIn, string|Carbon $checkOut, ?int $ignoreBookingId = null): int
    {
        $nights = $this->nights($checkIn, $checkOut);
        if ($nights < 1) {
            return 0;
        }

        $in = Carbon::parse($checkIn)->startOfDay();
        $out = Carbon::parse($checkOut)->startOfDay();
        $minFree = (int) $room->quantity;

        $blocks = RoomDateBlock::query()
            ->where('room_type_id', $room->id)
            ->whereBetween('date', [$in->toDateString(), $out->copy()->subDay()->toDateString()])
            ->get()
            ->keyBy(fn (RoomDateBlock $b) => $b->date->toDateString());

        foreach (CarbonPeriod::create($in, $out->copy()->subDay()) as $night) {
            $dateKey = $night->toDateString();
            $booked = HotelBooking::query()
                ->where('room_type_id', $room->id)
                ->whereIn('status', HotelBooking::ACTIVE_STATUSES)
                ->when($ignoreBookingId, fn ($q) => $q->where('id', '!=', $ignoreBookingId))
                ->where('check_in', '<=', $dateKey)
                ->where('check_out', '>', $dateKey)
                ->count();

            $blocked = (int) ($blocks->get($dateKey)?->blocked_units ?? 0);
            $minFree = min($minFree, (int) $room->quantity - $booked - $blocked);
        }

        return max(0, $minFree);
    }

    public function assertAvailable(RoomType $room, string|Carbon $checkIn, string|Carbon $checkOut, int $roomsNeeded = 1): void
    {
        $nights = $this->nights($checkIn, $checkOut);
        if ($nights < 1) {
            throw new \InvalidArgumentException('A data de saída deve ser depois da entrada.');
        }

        if ($this->availableUnits($room, $checkIn, $checkOut) < $roomsNeeded) {
            throw new \InvalidArgumentException('Sem quartos livres nestas datas.');
        }
    }

    public function quote(RoomType $room, string|Carbon $checkIn, string|Carbon $checkOut, int $guests = 1, string $paymentMode = 'full'): array
    {
        if ($guests < 1) {
            throw new \InvalidArgumentException('Indica pelo menos 1 hóspede.');
        }

        if ($guests > (int) $room->capacity) {
            throw new \InvalidArgumentException("Este quarto admite até {$room->capacity} hóspedes.");
        }

        if (! in_array($paymentMode, ['full', 'deposit'], true)) {
            throw new \InvalidArgumentException('Modo de pagamento inválido.');
        }

        $this->assertAvailable($room, $checkIn, $checkOut);
        $nights = $this->nights($checkIn, $checkOut);

        $in = Carbon::parse($checkIn)->startOfDay();
        $out = Carbon::parse($checkOut)->startOfDay();
        $total = 0.0;
        $nightPrices = [];

        $overrides = RoomDateBlock::query()
            ->where('room_type_id', $room->id)
            ->whereBetween('date', [$in->toDateString(), $out->copy()->subDay()->toDateString()])
            ->whereNotNull('price_override')
            ->get()
            ->keyBy(fn (RoomDateBlock $b) => $b->date->toDateString());

        foreach (CarbonPeriod::create($in, $out->copy()->subDay()) as $night) {
            $key = $night->toDateString();
            $price = (float) ($overrides->get($key)?->price_override ?? $room->price_per_night);
            $nightPrices[] = ['date' => $key, 'price' => $price];
            $total += $price;
        }

        $total = round($total, 2);
        $depositPercent = max(0, min(100, (int) ($room->deposit_percent ?? 30)));
        $depositAmount = $paymentMode === 'deposit'
            ? round($total * ($depositPercent / 100), 2)
            : $total;

        return [
            'room_type_id' => $room->id,
            'shop_id' => $room->shop_id,
            'check_in' => $in->toDateString(),
            'check_out' => $out->toDateString(),
            'guests' => $guests,
            'nights' => $nights,
            'price_per_night' => (float) $room->price_per_night,
            'night_prices' => $nightPrices,
            'total_price' => $total,
            'payment_mode' => $paymentMode,
            'deposit_percent' => $depositPercent,
            'deposit_amount' => $depositAmount,
            'amount_due_now' => $depositAmount,
            'available_units' => $this->availableUnits($room, $checkIn, $checkOut),
            'free_cancel_days' => (int) $room->free_cancel_days,
        ];
    }
}
