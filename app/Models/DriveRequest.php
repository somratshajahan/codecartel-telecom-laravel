<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'operator',
        'mobile',
        'amount',
        'status',
        'balance_type',
        'admin_status',
        'admin_note',
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
        'is_routed' => 'boolean',
        'charged_at' => 'datetime',
        'settled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(DrivePackage::class);
    }
}
