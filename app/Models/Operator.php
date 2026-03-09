<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Operator extends Model
{
    protected $fillable = [
        'name',
        'short_code',
        'description',
        'badge_text',
        'circle_bg_color',
        'logo_text',
        'logo_image_url',
        'sort_order',
        'is_active',
        'logo',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

