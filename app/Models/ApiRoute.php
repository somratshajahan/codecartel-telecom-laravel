<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'module_type',
        'module_name',
        'api_id',
        'service',
        'code',
        'priority',
        'prefix',
        'status',
    ];

    protected $casts = [
        'api_id' => 'integer',
        'priority' => 'integer',
    ];

    public function apiConnection(): BelongsTo
    {
        return $this->belongsTo(Api::class, 'api_id');
    }
}