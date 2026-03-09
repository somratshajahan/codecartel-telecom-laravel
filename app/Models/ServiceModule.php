<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceModule extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'minimum_amount',
        'maximum_amount',
        'minimum_length',
        'maximum_length',
        'auto_send_limit',
        'require_pin',
        'require_name',
        'require_nid',
        'require_sender',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'auto_send_limit' => 'decimal:2',
        'minimum_length' => 'integer',
        'maximum_length' => 'integer',
        'require_pin' => 'boolean',
        'require_name' => 'boolean',
        'require_nid' => 'boolean',
        'require_sender' => 'boolean',
        'sort_order' => 'integer',
    ];
}