<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlexiRequest extends Model
{
    protected $fillable = [
        'user_id',
        'operator',
        'mobile',
        'amount',
        'cost',
        'balance_type',
        'type',
        'trnx_id',
        'status',
        'is_routed',
        'route_api_id',
        'remote_request_id',
        'source_request_id',
        'source_request_type',
        'source_api_key',
        'source_callback_url',
        'source_client_domain',
        'charged_at',
        'settled_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cost' => 'decimal:2',
        'is_routed' => 'boolean',
        'charged_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
