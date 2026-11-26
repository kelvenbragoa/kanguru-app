<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LedgerEntry extends Model
{
    public const TYPE_PARTNER = 'partner';
    public const TYPE_COURIER = 'courier';
    public const TYPE_PLATFORM = 'platform';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAYABLE = 'payable';
    public const STATUS_PAID = 'paid';
    public const STATUS_VOID = 'void';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payout()
    {
        return $this->belongsTo(Payout::class);
    }
}
