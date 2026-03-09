<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branding extends Model
{
    use HasFactory;

    // Jey field gulo form theke ashbe segulo ekhane allow korte hobe
    protected $fillable = [
        'brand_name',
        'top_title',
        'footer',
        'registration_system',
        'drive_system',
        'drive_balance',
        'regular_pack',
        'modem_connection',
        'modem_pass',
        'request_success_type',
        'success_list',
        'cancel_list',
        'sms_provider',
        'sms_user',
        'sms_password',
        'bkash',
        'rocket',
        'nagad',
        'upay',
        'sslcommerz_store_id',
        'sslcommerz_store_password',
        'sslcommerz_mode',
        'amarpay_store_id',
        'amarpay_signature_key',
        'amarpay_mode',
        'alert_no',
        'whatsapp_link',
        'telegram_link',
        'youtube_channel',
        'shopping_link',
        'image_1_link',
        'image_2_link',
        'image_3_link',
        'image_4_link',
        'meta_description',
        'keywords'
    ];
}
