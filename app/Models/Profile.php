<?php

namespace App\Models;

use App\Support\MediaPath;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'cash_blocked' => 'boolean',
    ];

    public function setAvatarAttribute($value): void
    {
        $this->attributes['avatar'] = MediaPath::normalize($value);
    }
}
