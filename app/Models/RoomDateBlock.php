<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoomDateBlock extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'blocked_units' => 'integer',
        'price_override' => 'decimal:2',
    ];

    public function roomType()
    {
        return $this->belongsTo(RoomType::class);
    }
}
