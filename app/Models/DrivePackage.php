<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DrivePackage extends Model
{
    protected $fillable = [
        'operator',
        'name',
        'price',
        'commission',
        'expire',
        'countdown_minutes',
        'offer_ends_at',
        'status',
        'sell_today',
        'amount',
        'comm'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission' => 'decimal:2',
        'expire' => 'date',
        'countdown_minutes' => 'integer',
        'offer_ends_at' => 'datetime',
        'sell_today' => 'integer',
        'amount' => 'decimal:2',
        'comm' => 'decimal:2'
    ];
}
