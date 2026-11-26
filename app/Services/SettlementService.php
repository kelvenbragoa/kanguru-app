<?php

namespace App\Services;

use App\Models\HotelBooking;
use App\Models\LedgerEntry;
use App\Models\Order;
use App\Models\ServiceBooking;
use App\Models\Shop;
use Illuminate\Support\Facades\DB;

class SettlementService
{
    public function settleOrder(Order $order): void
    {
        $order->loadMissing(['shop', 'orderType', 'orderItems', 'orderStatus']);
        if ($order->settled_at || $order->orderStatus?->name !== 'delivered') {
            return;
        }

        DB::transaction(function () use ($order) {
            $order = Order::query()->lockForUpdate()->with(['shop', 'orderType', 'orderItems'])->findOrFail($order->id);
            if ($order->settled_at) {
                return;
            }

            $isTaxi = $order->orderType?->name === 'Táxi';
            $subtotal = max(0, (float) $order->total_price - (float) $order->delivery_fee - (float) ($order->service_fee ?? 0));
            $deliveryFee = (float) $order->delivery_fee + (float) ($order->wait_fee ?? 0);
            $serviceFee = (float) ($order->service_fee ?? 0);

            if ($isTaxi) {
                $fare = (float) $order->total_price;
                $platform = round($fare * (float) config('kanguro.taxi_platform_commission'), 2);
                $courier = round($fare - $platform, 2);
                $this->post('order', $order->id, LedgerEntry::TYPE_COURIER, $order->agent_user_id, null, $order->agent_user_id, $courier, 'Ganho da viagem '.$order->code);
                $this->post('order', $order->id, LedgerEntry::TYPE_PLATFORM, null, null, null, $platform, 'Comissão Kanguro viagem '.$order->code);
                $order->update([
                    'commission_amount' => $platform,
                    'partner_due' => 0,
                    'courier_due' => $courier,
                    'settled_at' => now(),
                ]);

                if ($platform > 0 && $order->agent_user_id) {
                    app(DriverWalletService::class)->debitCommission(
                        $order,
                        $platform,
                        'Comissão Kanguro viagem '.$order->code
                    );
                }

                return;
            }

            $rate = (float) ($order->shop?->commission_rate ?? config('kanguro.partner_commission_rate'));
            $commission = round($subtotal * $rate, 2);
            $partnerDue = round($subtotal - $commission, 2);
            $courierShare = round($deliveryFee * (float) config('kanguro.courier_delivery_share'), 2);
            $platformDelivery = round($deliveryFee - $courierShare, 2);
            $platformTotal = round($commission + $platformDelivery + $serviceFee, 2);

            if ($order->shop_id && $partnerDue > 0) {
                $this->post(
                    'order',
                    $order->id,
                    LedgerEntry::TYPE_PARTNER,
                    $order->shop->owner_user_id,
                    $order->shop_id,
                    $order->shop->owner_user_id,
                    $partnerDue,
                    'Vendas líquidas '.$order->code
                );
            }

            if ($order->agent_user_id && $courierShare > 0) {
                $this->post(
                    'order',
                    $order->id,
                    LedgerEntry::TYPE_COURIER,
                    $order->agent_user_id,
                    null,
                    $order->agent_user_id,
                    $courierShare,
                    'Taxa de entrega '.$order->code
                );
            }

            if ($platformTotal > 0) {
                $this->post('order', $order->id, LedgerEntry::TYPE_PLATFORM, null, $order->shop_id, null, $platformTotal, 'Receita Kanguro '.$order->code);
            }

            $order->update([
                'commission_amount' => $commission,
                'partner_due' => $partnerDue,
                'courier_due' => $courierShare,
                'settled_at' => now(),
            ]);

            // Independentes: Kanguro retém a parte da taxa de entrega no saldo pré-pago
            if ($platformDelivery > 0 && $order->agent_user_id) {
                app(DriverWalletService::class)->debitCommission(
                    $order,
                    $platformDelivery,
                    'Comissão entrega Kanguro '.$order->code
                );
            }
        });
    }

    public function settleHotel(HotelBooking $booking): void
    {
        if ($booking->status !== HotelBooking::STATUS_COMPLETED) {
            return;
        }

        $this->postHotelSettlement($booking, (float) $booking->total_price, 'Reserva hotel '.$booking->code);
    }

    public function settleHotelNoShow(HotelBooking $booking): void
    {
        if ($booking->status !== HotelBooking::STATUS_NO_SHOW) {
            return;
        }

        $amount = (float) ($booking->amount_paid ?: $booking->deposit_amount ?: $booking->total_price);
        $this->postHotelSettlement($booking, $amount, 'No-show hotel '.$booking->code);
    }

    private function postHotelSettlement(HotelBooking $booking, float $total, string $label): void
    {
        $existing = LedgerEntry::query()
            ->where('reference_type', 'hotel_booking')
            ->where('reference_id', $booking->id)
            ->exists();
        if ($existing || $total <= 0) {
            return;
        }

        $booking->loadMissing('shop');
        $rate = (float) ($booking->shop?->commission_rate ?? config('kanguro.hotel_commission_rate'));
        $commission = round($total * $rate, 2);
        $partner = round($total - $commission, 2);

        if ($booking->shop_id && $partner > 0) {
            $this->post('hotel_booking', $booking->id, LedgerEntry::TYPE_PARTNER, $booking->shop->owner_user_id, $booking->shop_id, $booking->shop->owner_user_id, $partner, $label);
        }
        if ($commission > 0) {
            $this->post('hotel_booking', $booking->id, LedgerEntry::TYPE_PLATFORM, null, $booking->shop_id, null, $commission, 'Comissão '.$label);
        }
    }

    public function settleService(ServiceBooking $booking): void
    {
        if ($booking->status !== ServiceBooking::STATUS_COMPLETED) {
            return;
        }

        $existing = LedgerEntry::query()
            ->where('reference_type', 'service_booking')
            ->where('reference_id', $booking->id)
            ->exists();
        if ($existing) {
            return;
        }

        $booking->loadMissing('shop');
        $total = (float) $booking->price;
        $rate = (float) ($booking->shop?->commission_rate ?? config('kanguro.services_commission_rate'));
        $commission = round($total * $rate, 2);
        $partner = round($total - $commission, 2);

        if ($booking->shop_id && $partner > 0) {
            $this->post('service_booking', $booking->id, LedgerEntry::TYPE_PARTNER, $booking->shop->owner_user_id, $booking->shop_id, $booking->shop->owner_user_id, $partner, 'Serviço '.$booking->code);
        }
        if ($commission > 0) {
            $this->post('service_booking', $booking->id, LedgerEntry::TYPE_PLATFORM, null, $booking->shop_id, null, $commission, 'Comissão serviço '.$booking->code);
        }
    }

    public function serviceFeeFor(float $subtotal): float
    {
        $flat = (float) config('kanguro.service_fee_flat');
        $rate = (float) config('kanguro.service_fee_rate');

        return round($flat + max(0, $subtotal) * $rate, 2);
    }

    private function post(
        string $referenceType,
        int $referenceId,
        string $beneficiaryType,
        ?int $beneficiaryId,
        ?int $shopId,
        ?int $userId,
        float $amount,
        string $description,
    ): void {
        if ($amount <= 0) {
            return;
        }

        LedgerEntry::create([
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'beneficiary_type' => $beneficiaryType,
            'beneficiary_id' => $beneficiaryId,
            'shop_id' => $shopId,
            'user_id' => $userId,
            'amount' => $amount,
            'currency' => 'MZN',
            'description' => $description,
            'status' => LedgerEntry::STATUS_PAYABLE,
        ]);
    }
}
