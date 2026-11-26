<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\DispatchService;
use Illuminate\Console\Command;

class DispatchDueCouriers extends Command
{
    protected $signature = 'kanguro:dispatch-due-couriers';

    protected $description = 'Despacha estafetas para pedidos cuja hora de chamada já passou';

    public function handle(DispatchService $dispatch): int
    {
        $orders = Order::query()
            ->whereNull('agent_user_id')
            ->whereNotNull('courier_call_at')
            ->where('courier_call_at', '<=', now())
            ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['preparing', 'ready', 'confirmed']))
            ->orderBy('courier_call_at')
            ->limit(50)
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            $driver = $dispatch->tryAssign($order->fresh(), true, true);
            if ($driver) {
                $count++;
            }
        }

        $this->info("Despachados: {$count}");

        return self::SUCCESS;
    }
}
