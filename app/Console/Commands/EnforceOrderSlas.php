<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderException;
use App\Models\TrackingOrder;
use App\Services\DispatchService;
use App\Services\NotificationService;
use App\Services\ProductStockService;
use Illuminate\Console\Command;

class EnforceOrderSlas extends Command
{
    protected $signature = 'kanguro:enforce-order-slas';

    protected $description = 'Aplica SLAs do PDF: parceiro sem resposta e sem estafeta';

    public function handle(
        ProductStockService $stock,
        DispatchService $dispatch,
        NotificationService $notifications,
    ): int {
        $timeout = (int) config('kanguro.partner_accept_timeout_minutes');
        $warn = (int) config('kanguro.partner_accept_warn_minutes');
        $noCourier = (int) config('kanguro.no_courier_escalate_minutes');

        $pending = Order::query()
            ->with(['shop', 'user', 'orderStatus'])
            ->whereNotNull('shop_id')
            ->whereNotNull('accept_deadline_at')
            ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['pending', 'confirmed']))
            ->whereNull('shop_accepted_at')
            ->get();

        foreach ($pending as $order) {
            $deadline = $order->accept_deadline_at;
            if (! $deadline) {
                continue;
            }

            if ($deadline->copy()->subMinutes(max(0, $timeout - $warn))->isPast()
                && $deadline->isFuture()
                && $order->shop?->owner_user_id
                && ($owner = $order->shop->owner)) {
                $notifications->notify(
                    $owner,
                    'Pedido a expirar',
                    'O pedido '.$order->code.' expira em breve sem aceitação.',
                    'order_accept_warn',
                    ['order_id' => $order->id]
                );
            }

            if ($deadline->isPast()) {
                $cancelledId = Order::statusId('cancelled') ?? 9;
                $order->update(['order_status_id' => $cancelledId]);
                $order->payments()->where('status', 'pending')->update(['status' => 'failed']);
                $order->payments()->where('status', 'completed')->update(['status' => 'refunded']);
                TrackingOrder::create([
                    'order_id' => $order->id,
                    'order_status_id' => $cancelledId,
                    'description' => 'Cancelado: parceiro não respondeu a tempo',
                    'local' => $order->origin,
                ]);
                OrderException::create([
                    'order_id' => $order->id,
                    'type' => 'partner_no_response',
                    'description' => 'SLA de aceitação expirado',
                    'cost_bearer' => OrderException::defaultBearer('partner_no_response'),
                    'status' => OrderException::STATUS_OPEN,
                ]);
                $stock->releaseForOrder($order);
                if ($order->user) {
                    $notifications->notify(
                        $order->user,
                        'Pedido cancelado',
                        'O parceiro não respondeu a tempo. Foi feito o reembolso.',
                        'order_cancelled',
                        ['order_id' => $order->id]
                    );
                }
                $this->info('Cancelado por SLA parceiro: '.$order->code);
            }
        }

        $waitingCourier = Order::query()
            ->with(['user', 'orderStatus'])
            ->whereNull('agent_user_id')
            ->whereNotNull('courier_call_at')
            ->where('courier_call_at', '<=', now()->subMinutes($noCourier))
            ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['preparing', 'ready', 'confirmed']))
            ->get();

        foreach ($waitingCourier as $order) {
            $exists = OrderException::query()
                ->where('order_id', $order->id)
                ->where('type', 'no_courier')
                ->where('status', OrderException::STATUS_OPEN)
                ->exists();
            if ($exists) {
                continue;
            }

            OrderException::create([
                'order_id' => $order->id,
                'type' => 'no_courier',
                'description' => 'Nenhum estafeta disponível após '.$noCourier.' min',
                'cost_bearer' => OrderException::defaultBearer('no_courier'),
                'status' => OrderException::STATUS_OPEN,
            ]);

            if ($order->user) {
                $notifications->notify(
                    $order->user,
                    'À procura de estafeta',
                    'Ainda não há estafeta para o pedido '.$order->code.'. O apoio foi alertado.',
                    'no_courier',
                    ['order_id' => $order->id]
                );
            }
            $this->info('Escalado sem estafeta: '.$order->code);
        }

        return self::SUCCESS;
    }
}
