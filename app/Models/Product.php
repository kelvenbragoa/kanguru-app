<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'weight' => 'decimal:2',
    ];

    public function setImageAttribute($value): void
    {
        $this->attributes['image'] = MediaPath::normalize($value);
    }

    // Relacionamentos
    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function productStatus()
    {
        return $this->belongsTo(ProductStatus::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->whereHas('productStatus', function ($q) {
            $q->where('name', 'Ativo');
        });
    }

    public function scopeByCategory($query, $categoryName)
    {
        return $query->whereHas('productCategory', function ($q) use ($categoryName) {
            $q->where('name', $categoryName);
        });
    }
}
