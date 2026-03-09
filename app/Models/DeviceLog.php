<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceLog extends Model
{
    use HasFactory;

    // Database table-er nam jodi 'device_logs' hoy tobe eti dorkar nei, 
    // kintu onno kisu hole ekhane likhe dite hobe.
    protected $table = 'device_logs';

    // Ei column-gulo database-e mass assignment (save/update) korar permission deya holo
    protected $fillable = [
        'ip_address',
        'username',
        'browser_os',
        'two_step_verified',
        'status',
    ];

    // Optional: jodi 'created_at' column thake kintu apni seta date format-e chanchhen
    protected $casts = [
        'two_step_verified' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
