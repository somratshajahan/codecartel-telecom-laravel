<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Api extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'api_key',
        'provider',
        'api_url',
        'client_domain',
        'status',
        'balance',
        'main_balance',
        'drive_balance',
        'bank_balance',
    ];

    protected $casts = [
        'balance' => 'float',
        'main_balance' => 'float',
        'drive_balance' => 'float',
        'bank_balance' => 'float',
    ];

    public function approval(): HasOne
    {
        return $this->hasOne(ApiConnectionApproval::class, 'api_id');
    }

    public function approvalStatus(): int
    {
        return (int) ($this->approval?->status ?? 0);
    }
}
