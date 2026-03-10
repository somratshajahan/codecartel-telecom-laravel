<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SslCommerzTransaction extends Model
{
    protected $table = 'sslcommerz_transactions';

    protected $fillable = [
        'user_id',
        'tran_id',
        'session_key',
        'amount',
        'currency',
        'status',
        'gateway_status',
        'gateway_url',
        'validated_amount',
        'bank_tran_id',
        'card_type',
        'store_amount',
        'validation_id',
        'request_payload',
        'init_response_payload',
        'validation_payload',
        'callback_payload',
        'failure_reason',
        'validated_at',
        'credited_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'validated_amount' => 'decimal:2',
        'store_amount' => 'decimal:2',
        'request_payload' => 'array',
        'init_response_payload' => 'array',
        'validation_payload' => 'array',
        'callback_payload' => 'array',
        'validated_at' => 'datetime',
        'credited_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
