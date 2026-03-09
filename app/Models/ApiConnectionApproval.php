<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiConnectionApproval extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_id',
        'status',
    ];

    protected $casts = [
        'api_id' => 'integer',
        'status' => 'integer',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(Api::class, 'api_id');
    }
}