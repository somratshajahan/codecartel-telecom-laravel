<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    
    protected $fillable = [
        'subject',
        'message',
        'sender_email',
        'status',
        'reply'
    ];

 
    public function user()
    {
        return $this->belongsTo(User::class, 'sender_email', 'email');
    }
}