<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class ProductStockService
{
    public function reserveForOrder(Order $order): void
    {
        $order->loadMissing('orderItems');
        if ($order->orderItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($order) {
            foreach ($order->orderItems as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (! $product || $product->stock_qty === null) {
                    continue;
                }

                if ($product->stock_qty < $item->quantity) {
                    throw new \InvalidArgumentException(
                        "Stock insuficiente para {$product->name}."
                    );
                }

                $product->stock_qty -= (int) $item->quantity;
                if ($product->stock_qty <= 0) {
                    $product->stock_qty = 0;
                    $soldOut = ProductStatus::query()->where('name', 'Esgotado')->value('id');
                    if ($soldOut) {
                        $product->product_status_id = $soldOut;
                    }
                }
                $product->save();
                StockMovement::create([
                    'product_id' => $product->id,
                    'shop_id' => $product->shop_id,
                    'order_id' => $order->id,
                    'delta' => -1 * (int) $item->quantity,
                    'stock_after' => $product->stock_qty,
                    'reason' => 'order_reserve',
                    'note' => 'Reserva do pedido '.$order->code,
                ]);
            }
        });
    }

    public function releaseForOrder(Order $order): void
    {
        $order->loadMissing('orderItems');
        if ($order->orderItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($order) {
            $activeId = ProductStatus::query()->where('name', 'Ativo')->value('id');

            foreach ($order->orderItems as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);
                if (! $product || $product->stock_qty === null) {
                    continue;
                }

                $product->stock_qty += (int) $item->quantity;
                if ($product->stock_qty > 0 && $activeId) {
                    $soldOut = ProductStatus::query()->where('name', 'Esgotado')->value('id');
                    if ((int) $product->product_status_id === (int) $soldOut) {
                        $product->product_status_id = $activeId;
                    }
                }
                $product->save();
                StockMovement::create([
                    'product_id' => $product->id,
                    'shop_id' => $product->shop_id,
                    'order_id' => $order->id,
                    'delta' => (int) $item->quantity,
                    'stock_after' => $product->stock_qty,
                    'reason' => 'order_release',
                    'note' => 'Devolução do pedido '.$order->code,
                ]);
            }
        });
    }
}
