<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    protected $guarded = [];

    protected $casts = [
        'delta' => 'integer',
        'stock_after' => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
