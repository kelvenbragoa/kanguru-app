<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteLead extends Model
{
    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
    ];
}
