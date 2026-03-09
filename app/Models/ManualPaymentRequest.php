<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManualPaymentRequest extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'sender_number',
        'transaction_id',
        'amount',
        'note',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}