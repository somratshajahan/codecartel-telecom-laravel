<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveRequest extends Model
{
    protected $fillable = ['user_id', 'package_id', 'operator', 'mobile', 'amount', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(DrivePackage::class);
    }
}
