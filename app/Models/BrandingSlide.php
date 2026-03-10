<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrandingSlide extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_number',
        'image_path',
        'is_active',
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'is_active' => 'boolean',
    ];
}