<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DriverEarningsController extends Controller
{
    public function show(Request $request)
    {
        $period = $request->string('period')->toString() ?: 'week';
        if (! in_array($period, ['today', 'week', 'month', 'all'], true)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid period',
            ], 422);
        }

        [$from, $to, $label] = $this->range($period);
        $driverId = $request->user()->id;

        $periodOrders = $this->deliveredOrders($driverId)
            ->when($from, fn ($query) => $query->where('delivered_at', '>=', $from))
            ->when($to, fn ($query) => $query->where('delivered_at', '<=', $to))
            ->get();

        $lifetime = $this->deliveredOrders($driverId)->get();
        [$monthFrom, $monthTo] = $this->range('month');
        $monthOrders = $this->deliveredOrders($driverId)
            ->where('delivered_at', '>=', $monthFrom)
            ->where('delivered_at', '<=', $monthTo)
            ->get();
        [$weekFrom, $weekTo] = $this->range('week');
        $weekOrders = $this->deliveredOrders($driverId)
            ->where('delivered_at', '>=', $weekFrom)
            ->where('delivered_at', '<=', $weekTo)
            ->get();

        $days = $this->groupByDay($periodOrders, $from, $to, $period);

        return response()->json([
            'status' => 'success',
            'data' => [
                'period' => $period,
                'period_label' => $label,
                'from' => $from?->toIso8601String(),
                'to' => $to?->toIso8601String(),
                'currency' => 'MZN',
                'total' => round((float) $periodOrders->sum('delivery_fee'), 2),
                'deliveries' => $periodOrders->count(),
                'worked_minutes' => $this->workedMinutes($periodOrders),
                'average_per_delivery' => $periodOrders->isEmpty()
                    ? 0
                    : round((float) $periodOrders->avg('delivery_fee'), 2),
                'days' => $days,
                'recent' => $periodOrders
                    ->sortByDesc('delivered_at')
                    ->take(10)
                    ->values()
                    ->map(fn (Order $order) => [
                        'order_id' => $order->id,
                        'code' => $order->code,
                        'amount' => (float) $order->delivery_fee,
                        'delivered_at' => $order->delivered_at?->toIso8601String(),
                        'origin' => $order->origin,
                        'destination' => $order->destination,
                    ]),
                'lifetime' => [
                    'total' => round((float) $lifetime->sum('delivery_fee'), 2),
                    'deliveries' => $lifetime->count(),
                ],
                'month' => [
                    'total' => round((float) $monthOrders->sum('delivery_fee'), 2),
                    'deliveries' => $monthOrders->count(),
                ],
                'week' => [
                    'total' => round((float) $weekOrders->sum('delivery_fee'), 2),
                    'deliveries' => $weekOrders->count(),
                ],
            ],
        ]);
    }

    private function deliveredOrders(int $driverId)
    {
        return Order::query()
            ->where('agent_user_id', $driverId)
            ->whereHas('orderStatus', fn ($q) => $q->where('name', 'delivered'))
            ->whereNotNull('delivered_at');
    }

    /**
     * @return array{0: ?Carbon, 1: ?Carbon, 2: string}
     */
    private function range(string $period): array
    {
        $now = Carbon::now('Africa/Maputo');

        return match ($period) {
            'today' => [
                $now->copy()->startOfDay()->utc(),
                $now->copy()->endOfDay()->utc(),
                'Hoje',
            ],
            'week' => [
                $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay()->utc(),
                $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay()->utc(),
                'Esta semana',
            ],
            'month' => [
                $now->copy()->startOfMonth()->startOfDay()->utc(),
                $now->copy()->endOfMonth()->endOfDay()->utc(),
                'Este mês',
            ],
            default => [null, null, 'Tudo'],
        };
    }

    private function groupByDay($orders, ?Carbon $from, ?Carbon $to, string $period): array
    {
        $weekdays = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 7 => 'Dom'];
        $grouped = $orders->groupBy(function (Order $order) {
            return $order->delivered_at->timezone('Africa/Maputo')->toDateString();
        });

        if ($period === 'all' || $from === null || $to === null) {
            return $grouped->map(function ($dayOrders, $date) use ($weekdays) {
                $day = Carbon::parse($date, 'Africa/Maputo');

                return [
                    'date' => $date,
                    'label' => $weekdays[$day->dayOfWeekIso].' '.$day->format('d/m'),
                    'amount' => round((float) $dayOrders->sum('delivery_fee'), 2),
                    'deliveries' => $dayOrders->count(),
                ];
            })->sortKeys()->values()->all();
        }

        $cursor = $from->copy()->timezone('Africa/Maputo')->startOfDay();
        $end = $to->copy()->timezone('Africa/Maputo')->startOfDay();
        $days = [];

        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $dayOrders = $grouped->get($key, collect());
            $days[] = [
                'date' => $key,
                'label' => $weekdays[$cursor->dayOfWeekIso].' '.$cursor->format('d/m'),
                'amount' => round((float) $dayOrders->sum('delivery_fee'), 2),
                'deliveries' => $dayOrders->count(),
            ];
            $cursor->addDay();
        }

        return $days;
    }

    private function workedMinutes($orders): int
    {
        return (int) $orders->sum(function (Order $order) {
            if (! $order->collected_at || ! $order->delivered_at) {
                return 0;
            }

            return max(0, $order->collected_at->diffInMinutes($order->delivered_at));
        });
    }
}
