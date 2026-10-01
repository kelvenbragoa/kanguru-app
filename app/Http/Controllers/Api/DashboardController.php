<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shop;
use App\Models\User;
use App\Models\Vehicle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $todayStart = Carbon::now('Africa/Maputo')->startOfDay()->utc();
        $todayEnd = Carbon::now('Africa/Maputo')->endOfDay()->utc();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_orders' => Order::count(),
                'pending_orders' => Order::pending()->count(),
                'unassigned_orders' => Order::query()
                    ->whereNull('agent_user_id')
                    ->whereHas('orderStatus', fn ($q) => $q->whereIn('name', ['pending', 'confirmed']))
                    ->count(),
                'active_vehicles' => Vehicle::available()->count(),
                'online_drivers' => User::query()
                    ->where('is_active', true)
                    ->where('is_online', true)
                    ->whereHas('role', fn ($q) => $q->where('name', 'driver'))
                    ->count(),
                'pending_payments' => Payment::pending()->count(),
                'total_revenue' => Payment::completed()->sum('amount'),
                'delivered_today' => Order::query()
                    ->whereHas('orderStatus', fn ($q) => $q->where('name', 'delivered'))
                    ->whereBetween('delivered_at', [$todayStart, $todayEnd])
                    ->count(),
            ],
        ]);
    }

    public function reports(Request $request)
    {
        $days = min(max($request->integer('days', 14), 7), 90);
        $start = Carbon::now('Africa/Maputo')->subDays($days - 1)->startOfDay();
        $startUtc = $start->copy()->utc();

        $ordersByDay = Order::query()
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as total'))
            ->where('created_at', '>=', $startUtc)
            ->groupBy('day')
            ->pluck('total', 'day');

        $revenueByDay = Payment::query()
            ->select(DB::raw('DATE(COALESCE(paid_at, created_at)) as day'), DB::raw('SUM(amount) as total'))
            ->where('status', 'completed')
            ->where(function ($query) use ($startUtc) {
                $query->where('paid_at', '>=', $startUtc)
                    ->orWhere(function ($inner) use ($startUtc) {
                        $inner->whereNull('paid_at')->where('created_at', '>=', $startUtc);
                    });
            })
            ->groupBy('day')
            ->pluck('total', 'day');

        $series = [];
        $cursor = $start->copy();
        $end = Carbon::now('Africa/Maputo')->startOfDay();
        while ($cursor->lte($end)) {
            $key = $cursor->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $cursor->format('d/m'),
                'orders' => (int) ($ordersByDay[$key] ?? 0),
                'revenue' => round((float) ($revenueByDay[$key] ?? 0), 2),
            ];
            $cursor->addDay();
        }

        $byType = Order::query()
            ->select('order_type_id', DB::raw('COUNT(*) as orders'), DB::raw('COALESCE(SUM(total_price), 0) as revenue'))
            ->with('orderType:id,name')
            ->groupBy('order_type_id')
            ->get()
            ->map(fn (Order $row) => [
                'name' => $row->orderType?->name ?? '—',
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $byShop = Order::query()
            ->select('shop_id', DB::raw('COUNT(*) as orders'), DB::raw('COALESCE(SUM(total_price), 0) as revenue'))
            ->whereNotNull('shop_id')
            ->with('shop:id,name')
            ->groupBy('shop_id')
            ->orderByDesc('orders')
            ->limit(8)
            ->get()
            ->map(fn (Order $row) => [
                'name' => $row->shop?->name ?? '—',
                'orders' => (int) $row->orders,
                'revenue' => round((float) $row->revenue, 2),
            ]);

        $drivers = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'driver'))
            ->where('is_active', true)
            ->withCount([
                'agentOrders as deliveries' => fn ($q) => $q->whereHas('orderStatus', fn ($s) => $s->where('name', 'delivered')),
            ])
            ->withSum([
                'agentOrders as earnings' => fn ($q) => $q->whereHas('orderStatus', fn ($s) => $s->where('name', 'delivered')),
            ], 'delivery_fee')
            ->orderByDesc('deliveries')
            ->limit(10)
            ->get(['id', 'name', 'is_online'])
            ->map(fn (User $driver) => [
                'id' => $driver->id,
                'name' => $driver->name,
                'is_online' => (bool) $driver->is_online,
                'deliveries' => (int) $driver->deliveries,
                'earnings' => round((float) ($driver->earnings ?? 0), 2),
            ]);

        return response()->json([
            'status' => 'success',
            'data' => [
                'days' => $days,
                'series' => $series,
                'by_type' => $byType,
                'by_shop' => $byShop,
                'drivers' => $drivers,
                'shops_count' => Shop::count(),
            ],
        ]);
    }
}
