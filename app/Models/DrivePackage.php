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
        'status',
        'sell_today',
        'amount',
        'comm'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'commission' => 'decimal:2',
        'expire' => 'date',
        'sell_today' => 'integer',
        'amount' => 'decimal:2',
        'comm' => 'decimal:2'
    ];
}
