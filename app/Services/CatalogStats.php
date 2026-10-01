<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Collection;

class CatalogStats
{
    public function apply(Collection $shops): void
    {
        if ($shops->isEmpty()) {
            return;
        }

        $minutes = Order::query()
            ->whereIn('shop_id', $shops->pluck('id'))
            ->whereNotNull('collected_at')
            ->whereNotNull('delivered_at')
            ->get(['shop_id', 'collected_at', 'delivered_at'])
            ->groupBy('shop_id')
            ->map(function ($orders) {
                $average = $orders->avg(function ($order) {
                    return $order->collected_at->diffInMinutes($order->delivered_at);
                });

                return (int) round($average);
            });

        foreach ($shops as $shop) {
            $average = $shop->reviews_avg_rating;
            $shop->setAttribute('rating', $average === null ? null : round((float) $average, 1));
            $shop->setAttribute('review_count', (int) ($shop->reviews_count ?? 0));
            $shop->setAttribute('avg_delivery_minutes', $minutes[$shop->id] ?? null);
        }
    }
}
